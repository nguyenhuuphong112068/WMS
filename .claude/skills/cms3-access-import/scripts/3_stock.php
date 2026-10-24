<?php
/**
 * BƯỚC 3 - NHẬP TỒN ĐẦU KỲ (chemical_imports) từ lots.json (= Q_Ton QC1 / Q_Ton2 QC2).
 *
 * Mỗi lô Access còn tồn -> 1 phiếu nhập:
 *   amount = Ton (tồn hiện tại); imported_date / imported_by = Ngaynhap / Nguoinhap GỐC (chuyển đổi dữ liệu,
 *   giữ tuổi lô cho FEFO - khác quy tắc "ngày thao tác = now()" của màn nhập thường);
 *   expired_date = Hansudung; batch_no = Solo; invoice = Sohoadon/Ngaykyhoadon; supplier = MaNCC -> dbo_T_NCC;
 *   location_id = định khu mặc định của hoá chất phòng; is_microbiological_chemicals = HoaChatVS;
 *   check_result = passed (tồn đầu kỳ - nếu để pending thì toàn bộ tồn bị kẹt ở "Chờ kiểm tra").
 *
 * HẠN DÙNG NỘI BỘ: Monap = False (chưa mở nắp) => internal_expired_date = NULL ("chưa xác định"; HDNB của
 * Access lúc này chỉ là bản sao Hansudung). Khi mở nắp người dùng bấm "Xác định hạn dùng nội bộ" ở Tồn Kho
 * (= ngày xác định + shelf_life_months, không vượt hạn NSX). Monap = True => giữ HDNB.
 *
 * Mã Access -> mã danh mục: đọc lịch sử "Thêm mới" của chemical_categories (bước 2).
 * php 3_stock.php <thư mục JSON> [--dry]          Ra: <thư mục>/3_stock_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\ChemicalCode;
use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();
$num = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');

$lots = cms3_json("$dir/lots.json");
usort($lots, fn ($a, $b) => [$a['Dept'], $a['Ngaynhap'] ?? '', (string) $a['Maxn']] <=> [$b['Dept'], $b['Ngaynhap'] ?? '', (string) $b['Maxn']]);
$deptInfo = [];
foreach (cms3_json("$dir/dmhc_dept.json") as $r) {
    $deptInfo[$r['Bophan'] . '|' . $r['Mahoachat']] = $r;
}
$ncc = [];
foreach (cms3_json("$dir/ncc.json") as $n) {
    $ncc[cms3_clean($n['MaNCC'])] = $n;
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = round(($stats[$k] ?? 0) + $n, 3); };
$report = [];

DB::beginTransaction();
try {
    if (DB::table('chemical_exports')->count() && ! in_array('--force', $argv, true)) {
        throw new RuntimeException('Đã có phiếu xuất hoá chất - nạp lại tồn đầu sẽ lệch tồn. Thêm --force nếu chắc chắn.');
    }
    $old = DB::table('chemical_imports')->where('created_by', $ACTOR)->pluck('id');
    DB::table('chemical_import_histories')->whereIn('import_id', $old)->delete();
    DB::table('chemical_imports')->whereIn('id', $old)->delete();
    $stats['deleted_old'] = $old->count();

    $catOf = cms3_codes_from_history('chemical_category_histories', 'chemical_category_id');
    $catCode = DB::table('chemical_categories')->pluck('code', 'id');
    $dc = [];
    foreach (DB::table('chemical_department_categories as d')->join('units as u', 'u.id', '=', 'd.unit_id')
        ->get(['d.department_id', 'd.category_id', 'd.default_location_id', 'd.shelf_life_months', 'u.short_name']) as $r) {
        $dc[$r->department_id . '|' . $r->category_id] = $r;
    }

    $supplierByName = DB::table('suppliers')->pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id])->all();
    $supplierOf = [];
    $supplier = function ($code) use (&$supplierOf, &$supplierByName, $ncc, $ACTOR, $now, $inc) {
        $code = cms3_nullish($code);
        if ($code === null) {
            return null;
        }
        if (array_key_exists($code, $supplierOf)) {
            return $supplierOf[$code];
        }
        $n = $ncc[$code] ?? null;
        if (! $n || cms3_clean($n['TenNCC']) === '') {
            return $supplierOf[$code] = null;
        }
        $name = mb_substr(cms3_clean($n['TenNCC']), 0, 240);
        if (isset($supplierByName[mb_strtolower($name)])) {
            return $supplierOf[$code] = $supplierByName[mb_strtolower($name)];
        }
        $id = DB::table('suppliers')->insertGetId(['name' => $name, 'address' => cms3_nullish($n['DiaChi']) ? mb_substr(cms3_clean($n['DiaChi']), 0, 500) : null,
            'tax_no' => cms3_nullish($n['Masothue']), 'note' => 'Mã NCC CMS 3.0: ' . $code, 'app_status' => 'approved', 'status_id' => 1,
            'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DataMasterHistory::record('suppliers', $id, 'Thêm mới', 'Nạp từ CMS 3.0 (mã NCC ' . $code . '): ' . $name . '.',
            ['name' => 'Tên nhà cung cấp', 'address' => 'Địa chỉ', 'tax_no' => 'Mã số thuế']);
        $supplierByName[mb_strtolower($name)] = $id;
        $inc('suppliers_new');
        return $supplierOf[$code] = $id;
    };

    foreach ($lots as $l) {
        $bp = $l['Dept'];
        if (! isset(CMS3['departments'][$bp])) {
            continue;
        }
        $dept = CMS3['departments'][$bp]['id'];
        $cat = $catOf[$l['Mahoachat']] ?? null;
        $row = $cat ? ($dc[$dept . '|' . $cat] ?? null) : null;
        $line = [$bp, $l['Maxn'], $l['Mahoachat'], $cat ? $catCode[$cat] : '', '', $l['Ton'], '', $l['Hansudung'], '', $l['Solo'], ''];
        if (! $row) {
            $line[10] = 'Bỏ qua: hoá chất chưa khai cho phòng';
            $report[] = $line;
            $inc('skip_no_dept_row');
            continue;
        }
        $info = $deptInfo[$bp . '|' . $l['Mahoachat']] ?? [];
        $notes = [];
        $accessUnit = mb_strtolower(cms3_clean($info['DVT'] ?? ''));
        if ($accessUnit !== '' && $accessUnit !== mb_strtolower($row->short_name)) {
            $notes[] = 'ĐVT Access "' . cms3_clean($info['DVT']) . '" khác ĐVT phòng "' . $row->short_name . '" - chưa quy đổi';
            $inc('unit_mismatch');
        }
        $opened = ! empty($l['Monap']);
        $internal = $opened ? ($l['HDNB'] ?: null) : null;
        if (! $opened) {
            $inc((int) ($row->shelf_life_months ?? 0) > 0 ? 'unopened_waiting_internal_expiry' : 'unopened_no_shelf_life');
        }
        if ($l['Hansudung'] && $l['Hansudung'] < $now->format('Y-m-d')) {
            $notes[] = 'Lô đã quá hạn sử dụng';
            $inc('expired_lots');
        }
        $code = ChemicalCode::next($dept);
        $sup = $supplier($l['MaNCC'] ?? null);
        $note = 'Tồn đầu kỳ chuyển từ CMS 3.0 - mã XN ' . $l['Maxn'] . ', SL nhập gốc ' . $num($l['Soluongnhap'])
            . ((float) ($l['SoLuongCanDoi'] ?? 0) != 0 ? ', cân đối ' . $num($l['SoLuongCanDoi']) : '')
            . ', đã xuất ' . $num($l['TongXuat'])
            . ($opened ? ', đã mở nắp' . ($l['NgayMoNap'] ? ' ' . $l['NgayMoNap'] : '') : ', chưa mở nắp')
            . (cms3_nullish($l['Ghichu'] ?? '') ? ', ghi chú: ' . cms3_clean($l['Ghichu']) : '');
        $id = DB::table('chemical_imports')->insertGetId([
            'code' => $code, 'department_id' => $dept, 'category_id' => $cat, 'amount' => round((float) $l['Ton'], 4),
            'imported_date' => $l['Ngaynhap'] ?: $now->format('Y-m-d'), 'imported_by' => cms3_nullish($l['Nguoinhap'] ?? '') ?? $ACTOR,
            'invoice_number' => cms3_nullish($l['Sohoadon'] ?? '') ? mb_substr(cms3_clean($l['Sohoadon']), 0, 100) : null,
            'invoice_date' => $l['Ngaykyhoadon'] ?: null, 'expired_date' => $l['Hansudung'] ?: null, 'internal_expired_date' => $internal,
            'is_microbiological_chemicals' => ! empty($info['HoaChatVS']) ? 1 : 0,
            'batch_no' => cms3_nullish($l['Solo'] ?? '') ? mb_substr(cms3_clean($l['Solo']), 0, 100) : null,
            'supplier_id' => $sup, 'source_export_id' => null, 'transfer_item_id' => null, 'is_partial_lot' => 0,
            'location_id' => $row->default_location_id, 'note' => mb_substr($note, 0, 500), 'status_id' => 1,
            'is_checked' => 1, 'check_result' => 'passed', 'checked_by' => $ACTOR, 'checked_at' => $now, 'check_note' => 'Tồn đầu kỳ chuyển từ CMS 3.0',
            'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $r = DB::table('chemical_imports')->where('id', $id)->first();
        $hist = (array) $r;
        unset($hist['id'], $hist['department_id'], $hist['source_export_id'], $hist['transfer_item_id'], $hist['is_partial_lot'], $hist['updated_by'], $hist['updated_at']);
        DB::table('chemical_import_histories')->insert($hist + ['import_id' => $id, 'action' => 'Thêm mới', 'reason' => null,
            'change_note' => 'Tồn đầu kỳ chuyển từ CMS 3.0 (mã XN ' . $l['Maxn'] . '), mã xuất nhập ' . $code . '.']);
        $inc('imports_' . $bp);
        $inc('amount_' . $bp, (float) $l['Ton']);
        [$line[4], $line[6], $line[8], $line[10]] = [$code, $row->short_name, $internal ?? ($opened ? '' : 'chưa xác định'), implode(' | ', $notes)];
        $report[] = $line;
    }
    AuditTrialController::log('Thêm mới', 'chemical_imports', 0, 'NA', 'Nhập tồn đầu kỳ từ CMS 3.0: ' . json_encode(array_filter($stats,
        fn ($k) => str_starts_with($k, 'imports_'), ARRAY_FILTER_USE_KEY)), $ACTOR);

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/3_stock_report.csv", ['Bộ phận', 'Mã XN Access', 'Mã Access', 'Mã danh mục', 'Mã xuất nhập WMS', 'Tồn (SL nhập)',
    'ĐVT phòng', 'Hạn sử dụng', 'Hạn dùng nội bộ', 'Số lô', 'Ghi chú'], $report);
$stats['mode'] = $dry ? 'DRY-RUN (rollback)' : 'COMMITTED';
print_r($stats);
