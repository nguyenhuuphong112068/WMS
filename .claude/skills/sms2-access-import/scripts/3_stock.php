<?php
/**
 * BƯỚC 3 - TỒN ĐẦU KỲ CHẤT CHUẨN theo Q_Ton_QC1 / Q_Ton_QC2 (SMS 2.0).
 *
 *   Tồn của mã ống Maxn = Σ Round(Soluongnhap,3) - Σ Round(Soluongxuat,3) (đúng công thức Q_Ton_<BP>, không lọc
 *   dòng xuất). Mỗi Maxn còn tồn > 0 => 1 dòng standard_imports:
 *   - code = Maxn GIỮ NGUYÊN (nhãn đã in trên ống; vd QC1CNL26010204, cả mã đuôi "w", "QCCHC26010005.1");
 *     seq_year/seq_no tách từ mã để số thứ tự ống mới trên WMS chạy tiếp; group_code theo Loaichuan (CG -> PRS).
 *   - amount = Soluongnhap GỐC của Access; ống đã dùng dở thêm ĐÚNG 1 dòng standard_exports (type export) = Σ xuất,
 *     ngày = ngày xuất cuối, reason "Tổng xuất SMS 2.0 …" (người dùng chốt 26/10/2026 - để tỉ lệ đã dùng, sắp hết,
 *     độ lệch KSKL tính đúng trên lượng nhập ban đầu). Tồn WMS = nhập - tổng xuất = tồn Q_Ton.
 *     Theo ĐVT danh mục phòng - Soluongnhap của Access đã theo ĐVT đó; DVTQC chỉ là đơn vị của quy cách ống
 *     (lọ 110 mg) => ghi vào note, không quy đổi. Dòng phòng gộp mg/g mà lô theo ĐVT khác thì quy đổi qua factor_to_base.
 *   - Đã kiểm tra ĐẠT (is_checked = 1, check_result passed) - để chờ kiểm tra thì tồn bị kẹt.
 *   - Giữ Ngaynhap / Nguoinhap gốc (ngoại lệ có chủ đích với quy tắc "ngày thao tác = now()": chuyển đổi dữ liệu).
 *   - Hạn: CheckOnline / Hansudung >= 9000 -> 'check online'; Retest -> 'retest' (chu kỳ để trống); còn lại 'Specify'.
 *     HDhienthi là ngày sớm hơn Hansudung -> internal_expired_date.
 *   - KSKL: weight_controlled = CanBi, standard_form = ThuHinh; ống đã cân bì đầu ở SMS 2.0 (KLBiChuan > 0)
 *     -> gross_weight_before = KLBiChuan (WMS không bắt cân lại), KLBi > 0 và <= KLBiChuan -> tare_weight_after.
 *     Số cân luôn là mg với chuẩn ĐVT thể tích -> ghi tỉ trọng d (cột d của Access, bỏ d = 1 mặc định) vào
 *     standard_categories.density khi đang trống, để App\Support\StandardWeight quy KL thực ra ml.
 *   - requires_aliquot = TrietOng, stability_batch = DOD_CTC, coa_no = PKN, potency = HL, moisture = LOD,
 *     purpose_id = Mucdichsudung tách dấu phẩy -> Dữ liệu gốc "Chỉ tiêu kiểm" (purposes, tạo mới nếu chưa có),
 *     location_id = định khu mặc định của dòng danh mục phòng.
 *
 * CHẠY LẠI = xoá phiếu nhập + phiếu tổng xuất do script tạo rồi dựng lại (sms2_delete_stock); dừng nếu đã có
 * phiếu sử dụng / phiếu nhập người dùng tạo.
 * php 3_stock.php <thư mục JSON> [--dry]          Ra: <thư mục>/3_stock_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = SMS2['actor'];
$now = now();

// Loaichuan Access (= phần nhóm trong mã ống) => khoá config('standard.groups')
const SMS2_LOT_GROUPS = ['CG' => 'PRS', 'IMP' => 'IMPRS', 'CTC' => 'CTC', 'VKN' => 'VKN', 'CN' => 'CN', 'CHC' => 'CHC', 'CNL' => 'CNL'];
const SMS2_DENSITY_NOTE = 'Nạp từ SMS 2.0 - tỉ trọng KSKL';
const SMS2_FORMS = ['dạng bột rời' => 'Dạng Bột Rời', 'dạng bột min' => 'Dạng Bột Mịn', 'dang bột min' => 'Dạng Bột Mịn',
                    'nadạng bột min' => 'Dạng Bột Mịn', 'dạng bột mịn' => 'Dạng Bột Mịn', 'dạng sệt' => 'Dạng Sệt', 'dạng bột sệt' => 'Dạng Sệt'];

function sms2_date($v): ?string
{
    return $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : null;
}

/** 'dd/mm/yyyy' -> 'yyyy-mm-dd' */
function sms2_dmy($v): ?string
{
    return preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', trim((string) $v), $m)
        ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
}

$nhap = [];
$dupMaxn = [];
foreach (sms2_json("$dir/nhap.json") as $r) {
    if (! isset(SMS2['departments'][$r['Dept']])) {
        continue;
    }
    $k = $r['Dept'] . '|' . $r['Maxn'];
    if (isset($nhap[$k])) {
        $dupMaxn[$k] = true;
        $nhap[$k]['_in'] += round((float) $r['Soluongnhap'], 3);
        continue;
    }
    $nhap[$k] = $r + ['_in' => round((float) $r['Soluongnhap'], 3)];
}
$xuat = [];
foreach (sms2_json("$dir/xuat_sum.json") as $x) {
    $xuat[$x['Dept'] . '|' . $x['Maxn']] = $x;
}

$catOf = sms2_codes_from_history('standard_category_histories', 'standard_category_id');
$catCode = DB::table('standard_categories')->pluck('code', 'id');
$deptCat = [];
foreach (DB::table('standard_department_categories')->get() as $d) {
    $deptCat[$d->department_id . '|' . $d->category_id] = $d;
}
$units = DB::table('units')->get()->keyBy('id');
$deptUnitOf = []; // ĐVT Access của từng mã chuẩn trong danh mục phòng (Q_DMHC_<BP>HH)
foreach (sms2_json("$dir/dmhc_dept.json") as $d) {
    $deptUnitOf[$d['Bophan'] . '|' . $d['Machuan']] = $d['DVT'];
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu script tạo trước đó ---------- */
    sms2_delete_stock($argv, $inc);
    // Tỉ trọng d script đã điền vào danh mục ở lần chạy trước -> trả về trống rồi điền lại
    foreach (DB::table('standard_category_histories')->where('created_by', $ACTOR)->where('action', 'Cập nhật')
        ->where('change_note', 'like', SMS2_DENSITY_NOTE . '%')->get() as $h) {
        DB::table('standard_categories')->where('id', $h->standard_category_id)->where('density', $h->density)->update(['density' => null]);
        DB::table('standard_category_histories')->where('id', $h->id)->delete();
        $inc('cleanup_density');
    }
    $densities = [];
    $densityMissing = [];
    foreach (DB::table('purposes')->where('created_by', $ACTOR)->pluck('id') as $pid) {
        DB::table('datamaster_histories')->where('table_name', 'purposes')->where('record_id', $pid)->delete();
        DB::table('purposes')->where('id', $pid)->delete();
        $inc('cleanup_purposes');
    }

    /* ---------- 1. Chỉ tiêu kiểm (purposes) ---------- */
    $purposeId = [];
    $resolvePurpose = function (string $name) use (&$purposeId, $ACTOR, $now, $inc): int {
        $name = mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
        $k = mb_strtolower($name);
        if (! isset($purposeId[$k])) {
            $id = DB::table('purposes')->where('name', $name)->value('id'); // collation: hoà = hòa, hoa/thường
            if (! $id) {
                $id = DB::table('purposes')->insertGetId(['name' => mb_substr($name, 0, 255), 'status_id' => 1,
                    'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now]);
                DataMasterHistory::record('purposes', $id, 'Thêm mới', 'Nạp từ SMS 2.0 (Mục đích sử dụng của phiếu nhập chuẩn).',
                    ['name' => 'Tên chỉ tiêu kiểm']);
                $inc('purposes_new');
            }
            $purposeId[$k] = (int) $id;
        }
        return $purposeId[$k];
    };

    /* ---------- 2. Phiếu nhập = tồn đầu kỳ ---------- */
    $report = [];
    $usedCodes = [];
    foreach ($nhap as $k => $r) {
        $bp = $r['Dept'];
        $dept = SMS2['departments'][$bp]['id'];
        $x = $xuat[$k] ?? null;
        $out = $x ? (float) $x['TongXuat'] : 0.0;
        $ton = round($r['_in'] - $out, 4);
        $code = sms2_clean($r['Maxn']);
        $cat = $catOf[$r['Machuan']] ?? null;
        $line = [$bp, $code, $r['Machuan'], $cat ? $catCode[$cat] : '', $r['_in'], $out, $ton, sms2_clean($deptUnitOf[$bp . '|' . $r['Machuan']] ?? ''), '', '', ''];
        $issues = [];

        if ($ton <= 0.00005) {
            $inc($ton < -0.00005 ? 'skip_negative' : 'skip_zero');
            if ($ton < -0.00005) {
                $line[10] = 'Bỏ qua: tồn âm ở SMS 2.0';
                $report[] = $line;
            }
            continue;
        }
        if (! $cat || ! isset($deptCat[$dept . '|' . $cat])) {
            $line[10] = $cat ? 'Bỏ qua: phòng chưa khai dòng danh mục này' : 'Bỏ qua: mã chuẩn chưa có trong danh mục công ty';
            $report[] = $line;
            $inc('skip_no_category');
            continue;
        }
        $dc = $deptCat[$dept . '|' . $cat];
        if (isset($usedCodes[$code])) {
            $line[10] = 'Bỏ qua: trùng mã ống ' . $code;
            $report[] = $line;
            $inc('skip_duplicate_code');
            continue;
        }
        $usedCodes[$code] = true;
        if (isset($dupMaxn[$k])) {
            $issues[] = 'Mã ống có nhiều dòng nhập ở SMS 2.0 (đã cộng)';
        }

        // Lượng theo ĐVT danh mục phòng; dòng phòng gộp mg/g -> quy đổi (hệ số áp cho cả nhập lẫn tổng xuất)
        $factor = 1.0;
        $fromUnit = sms2_unit_id($deptUnitOf[$bp . '|' . $r['Machuan']] ?? '');
        if ($fromUnit && (int) $fromUnit !== (int) $dc->unit_id) {
            $f = $units[$fromUnit] ?? null;
            $t = $units[$dc->unit_id] ?? null;
            if ($f && $t && $f->unit_group === $t->unit_group && in_array($f->unit_group, ['mass', 'volume'], true)) {
                $factor = $f->factor_to_base / $t->factor_to_base;
                $issues[] = 'Quy đổi ' . $f->short_name . ' -> ' . $t->short_name . ' (x' . $factor . ')';
            } else {
                $issues[] = 'ĐVT lô (' . ($f->short_name ?? '?') . ') khác ĐVT phòng (' . ($t->short_name ?? '?') . ') - chưa quy đổi';
            }
        }
        // Ống dùng dở: nhập = Soluongnhap gốc + 1 phiếu sử dụng "tổng xuất SMS 2.0" (người dùng chốt 26/10/2026)
        $amountIn = round($r['_in'] * $factor, 4);
        $amountOut = round($out * $factor, 4);
        $amount = round($amountIn - $amountOut, 4);

        // Nhóm chuẩn: phần nhóm trong mã ống (= Loaichuan)
        $loai = sms2_clean($r['Loaichuan']);
        $groupKey = SMS2_LOT_GROUPS[$loai] ?? null;
        if (! $groupKey && preg_match('/^(?:QC\d?|AD)(CG|IMP|CTC|VKN|CN|CHC|CNL)/', $code, $m)) {
            $groupKey = SMS2_LOT_GROUPS[$m[1]];
        }
        if (! $groupKey) {
            $line[10] = 'Bỏ qua: không xác định được nhóm chuẩn (Loaichuan "' . $loai . '")';
            $report[] = $line;
            $inc('skip_no_group');
            continue;
        }
        $catGroups = \App\Support\StandardCode::decodeGroups(DB::table('standard_categories')->where('id', $cat)->value('groups'));
        if (! in_array($groupKey, $catGroups, true)) {
            $issues[] = 'Nhóm ' . $groupKey . ' không có trong danh mục (' . implode(',', $catGroups) . ')';
        }

        // Số thứ tự trong mã: QC1IMP26011037 -> năm 2026, STT 1037 (mã lạ: lấy năm nhập, STT 0).
        // Mã đầu QC / AD (không số) là ống chuyển từ bộ phận QC, AD sang, đuôi "w" / ".1" vẫn tách được.
        if (preg_match('/^(?:QC\d?|AD)(?:CG|IMP|CTC|VKN|CN|CHC|CNL)(\d{2})(\d{2})(\d{4,5})/', $code, $m)) {
            $seqYear = 2000 + (int) $m[1];
            $seqNo = (int) $m[3];
        } else {
            $seqYear = (int) substr((string) $r['Ngaynhap'], 0, 4);
            $seqNo = 0;
            $issues[] = 'Mã ống không đúng dạng, không tách được số thứ tự';
        }
        if (! preg_match('/^QC\d(?:CG|IMP|CTC|VKN|CN|CHC|CNL)\d{8}w?$/i', $code)) {
            $issues[] = 'Mã ống dạng đặc biệt (giữ nguyên)';
        }

        // Hạn dùng
        $hsd = sms2_date($r['Hansudung']);
        $online = ! empty($r['CheckOnline']) || ($hsd && $hsd >= '9000-01-01') || mb_strtolower(sms2_clean($r['HDhienthi'])) === 'check online';
        if ($online) {
            $expiryType = 'check online';
            $expired = null;
        } else {
            $expiryType = ! empty($r['Retest']) ? 'retest' : 'Specify';
            $expired = $hsd;
            if ($expired && $expired < $now->format('Y-m-d')) {
                $issues[] = 'Đã quá hạn (' . $expired . ')';
            }
        }
        $shown = sms2_dmy($r['HDhienthi']);
        $internal = (! $online && $shown && $hsd && $shown < $hsd) ? $shown : null;
        if ($internal) {
            $issues[] = 'Hạn dùng nội bộ ' . $internal . ' (theo HDhienthi)';
        }
        if (! empty($r['Monap']) && ! $internal && $dc->shelf_life_months) {
            $issues[] = 'Đã mở nắp - chưa có hạn dùng nội bộ';
        }

        // Chỉ tiêu kiểm
        $pids = [];
        foreach (preg_split('/\s*,\s*/u', sms2_clean($r['Mucdichsudung'])) as $p) {
            if (sms2_nullish($p) !== null) {
                $pids[] = $resolvePurpose($p);
            }
        }
        $pids = array_values(array_unique($pids));

        $weight = ! empty($r['CanBi']) ? 1 : 0;
        $form = $weight ? (SMS2_FORMS[mb_strtolower(sms2_clean($r['ThuHinh']))] ?? null) : null;

        // KSKL: ống đã cân Bì + Chuẩn ở SMS 2.0 thì chép sang (không bắt cân lại ở lần dùng đầu trên WMS);
        // KL bì sau khi dùng chỉ chép khi hợp lệ (0 < bì <= bì + chuẩn)
        $gross = $weight && (float) $r['KLBiChuan'] > 0 ? round((float) $r['KLBiChuan'], 4) : null;
        $tare = $gross !== null && (float) $r['KLBi'] > 0 && (float) $r['KLBi'] <= $gross ? round((float) $r['KLBi'], 4) : null;
        if ($gross !== null && (float) $r['KLBi'] > $gross) {
            $issues[] = 'KL Bì (' . (float) $r['KLBi'] . ') > KL Bì + Chuẩn - không chép KL Bì';
        }
        if ($weight && $gross === null && $out > 0) {
            $issues[] = 'KSKL đã dùng nhưng SMS 2.0 chưa có KL Bì + Chuẩn';
        }
        // ĐVT thể tích: WMS quy số cân (mg) ra ml bằng tỉ trọng của DANH MỤC -> gom d của Access để ghi vào danh mục.
        // d = 1 là giá trị mặc định của Access (không phải số đo) nên không dùng.
        $deptUnitGroup = $units[$dc->unit_id]->unit_group ?? null;
        if ($weight && $deptUnitGroup === 'volume') {
            if ((float) $r['d'] > 0 && (float) $r['d'] != 1.0) {
                $densities[$cat][] = (float) $r['d'];
            } else {
                $densityMissing[$cat] = $code;
            }
        }
        $na = fn ($v) => ($v = sms2_nullish($v)) === null || in_array(mb_strtoupper($v), ['NAN'], true) ? null : mb_substr($v, 0, 100);

        $noteParts = ['Tồn đầu kỳ từ SMS 2.0'];
        $q = sms2_clean($r['Quicach']);
        if ($q !== '') {
            $noteParts[] = 'quy cách ' . $q . ' ' . sms2_clean($r['DVTQC']);
        }
        if ($x) {
            $noteParts[] = 'đã xuất ' . $out . ' (' . $x['SoLanXuat'] . ' lần, ' . $x['NgayXuatDau'] . ' - ' . $x['NgayXuatCuoi'] . ')';
        }
        if (! empty($r['Monap'])) {
            $noteParts[] = 'đã mở nắp';
        }
        if (sms2_clean($r['SoPP']) !== '') {
            $noteParts[] = 'SoPP ' . sms2_clean($r['SoPP']);
        }
        if (($old2 = sms2_clean($r['Maxn_old2'])) !== '' && $old2 !== $code) {
            $noteParts[] = 'mã cũ ' . $old2;
        }
        foreach (['Ghichu', 'Khac'] as $f) {
            if (($g = sms2_nullish($r[$f])) !== null) {
                $noteParts[] = $g;
            }
        }

        $row = ['code' => mb_substr($code, 0, 50), 'department_id' => $dept, 'category_id' => $cat,
            'source_import_id' => null, 'transfer_item_id' => null,
            'group_code' => \App\Support\StandardCode::groupCode($groupKey), 'seq_year' => $seqYear, 'seq_no' => $seqNo,
            'amount' => $amountIn, 'imported_date' => sms2_date($r['Ngaynhap']) ?? $now->format('Y-m-d'),
            'gross_weight_before' => $gross, 'tare_weight_after' => $tare,
            'imported_by' => sms2_nullish($r['Nguoinhap']) ?? $ACTOR,
            'invoice_number' => sms2_nullish($r['Sohoadon']), 'invoice_date' => sms2_date($r['Ngaykyhoadon']),
            'expired_date' => $expired, 'expiry_type' => $expiryType, 'retest_interval_months' => null,
            'weight_controlled' => $weight, 'standard_form' => $form,
            'requires_aliquot' => ! empty($r['TrietOng']) ? 1 : 0, 'stability_batch' => ! empty($r['DOD_CTC']) ? 1 : 0,
            'internal_expired_date' => $internal, 'batch_no' => $na($r['Solo']), 'coa_no' => $na($r['PKN']),
            'potency' => $na($r['HL']), 'moisture' => $na($r['LOD']), 'supplier_id' => null,
            'purpose_id' => $pids ? json_encode($pids) : null, 'location_id' => $dc->default_location_id,
            'note' => mb_substr(implode('; ', $noteParts), 0, 500), 'status_id' => 1,
            'is_checked' => 1, 'check_result' => 'passed', 'checked_by' => $ACTOR, 'checked_at' => $now,
            'check_note' => 'Tồn đầu kỳ nạp từ SMS 2.0 (Q_Ton_' . $bp . ')',
            'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now];
        $id = DB::table('standard_imports')->insertGetId($row);
        DB::table('standard_import_histories')->insert(['standard_import_id' => $id, 'action' => 'Thêm mới']
            + array_intersect_key($row, array_flip(['code', 'category_id', 'group_code', 'amount', 'imported_date', 'imported_by',
                'invoice_number', 'invoice_date', 'expired_date', 'expiry_type', 'retest_interval_months', 'internal_expired_date',
                'batch_no', 'coa_no', 'potency', 'moisture', 'weight_controlled', 'standard_form', 'requires_aliquot',
                'stability_batch', 'supplier_id', 'purpose_id', 'location_id', 'note', 'status_id', 'is_checked', 'check_result',
                'checked_by', 'checked_at', 'check_note']))
            + ['change_note' => 'Nạp tồn đầu kỳ từ SMS 2.0 (Q_Ton_' . $bp . '), mã ống ' . $code . ', mã chuẩn Access '
                . $r['Machuan'] . '.', 'reason' => null, 'created_by' => $ACTOR, 'created_at' => $now]);

        // 1 phiếu sử dụng gộp toàn bộ lượng đã xuất ở SMS 2.0, ngày = ngày xuất cuối
        if ($amountOut > 0) {
            $exportAt = (sms2_date($x['NgayXuatCuoi'] ?? null) ?? $now->format('Y-m-d')) . ' 12:00:00';
            $reason = 'Tổng xuất SMS 2.0: ' . $x['SoLanXuat'] . ' lần, ' . $x['NgayXuatDau'] . ' - ' . $x['NgayXuatCuoi']
                . ($factor != 1.0 ? ' (' . $out . ' theo ĐVT Access)' : '');
            $exp = ['code' => $row['code'], 'import_id' => $id, 'request_item_id' => null, 'department_id' => $dept,
                'to_department_id' => null, 'transfer_item_id' => null, 'quarantine_id' => null, 'group_id' => null,
                'amount' => $amountOut, 'specification' => null, 'type' => 'export', 'product_name' => null, 'batch_no' => null,
                'testing' => null, 'reason' => mb_substr($reason, 0, 500), 'created_by' => $ACTOR,
                'created_at' => $exportAt, 'updated_at' => $now];
            $expId = DB::table('standard_exports')->insertGetId($exp);
            DB::table('standard_export_histories')->insert(['standard_export_id' => $expId, 'action' => 'Thêm mới',
                'code' => $exp['code'], 'import_id' => $id, 'amount' => $amountOut, 'type' => 'export', 'product_name' => null,
                'batch_no' => null, 'testing' => null, 'reason' => $exp['reason'],
                'change_note' => 'Nạp từ SMS 2.0: gộp ' . $x['SoLanXuat'] . ' lần xuất của mã ống ' . $code . ' thành 1 phiếu sử dụng.',
                'created_by' => $ACTOR, 'created_at' => $now]);
            $inc("exports_$bp");
        }
        if ($gross !== null) {
            $inc('kskl_gross_copied');
        }
        if ($tare !== null) {
            $inc('kskl_tare_copied');
        }

        $inc("lots_$bp");
        $stats["amount_in_$bp"] = round(($stats["amount_in_$bp"] ?? 0) + $amountIn, 4);
        $stats["amount_out_$bp"] = round(($stats["amount_out_$bp"] ?? 0) + $amountOut, 4);
        $stats["stock_$bp"] = round(($stats["stock_$bp"] ?? 0) + $amount, 4);
        $stats["stock_access_$bp"] = round(($stats["stock_access_$bp"] ?? 0) + $ton, 4);
        if (! $dc->default_location_id) {
            $inc('lots_no_location');
        }
        $line[8] = $expiryType . ($expired ? ' ' . $expired : '');
        $line[9] = $amountIn . ' / ' . $amountOut . ' / ' . $amount;
        $line[10] = implode(' | ', $issues);
        $line[11] = $gross ?? '';
        $line[12] = $tare ?? '';
        $report[] = $line;
    }

    /* ---------- 3. Tỉ trọng d cho danh mục ĐVT thể tích có KSKL (chỉ điền khi danh mục còn trống) ---------- */
    foreach ($densities as $cat => $values) {
        $d = (float) sms2_mode($values);
        $current = DB::table('standard_categories')->where('id', $cat)->first();
        if ($current->density !== null) {
            continue;
        }
        DB::table('standard_categories')->where('id', $cat)->update(['density' => $d, 'updated_by' => $ACTOR, 'updated_at' => $now]);
        $after = DB::table('standard_categories')->where('id', $cat)->first();
        DB::table('standard_category_histories')->insert(['standard_category_id' => $cat, 'action' => 'Cập nhật']
            + array_intersect_key((array) $after, array_flip(['code', 'chem_names_id', 'cas_no', 'manufacturers_id', 'density',
                'storage_condition_id', 'version', 'groups', 'shelf_life_months', 'doc_no', 'note', 'app_status', 'status_id',
                'lead_time_days']))
            + ['change_note' => SMS2_DENSITY_NOTE . ': Tỷ trọng d (g/ml): Trống -> ' . $d
                . (count(array_unique($values)) > 1 ? ' (SMS 2.0 có nhiều giá trị: ' . implode(', ', array_unique($values)) . ')' : '') . '.',
               'change_reason' => 'Chuyển đổi dữ liệu SMS 2.0 (KSKL chuẩn đo thể tích)', 'created_by' => $ACTOR, 'created_at' => $now]);
        $inc('category_density_set');
        unset($densityMissing[$cat]);
    }
    foreach ($densityMissing as $cat => $code) {
        if (DB::table('standard_categories')->where('id', $cat)->value('density') === null) {
            $inc('category_density_missing');
            echo "  ! Thiếu tỉ trọng d: danh mục {$catCode[$cat]} (ống $code) - KSKL chưa tính được, khai ở Danh Mục Chất Chuẩn\n";
        }
    }

    sms2_write_csv("$dir/3_stock_report.csv",
        ['Bộ phận', 'Mã ống chuẩn', 'Mã chuẩn Access', 'Mã danh mục WMS', 'SL nhập', 'Đã xuất', 'Tồn SMS 2.0', 'ĐVT',
         'Hạn dùng WMS', 'WMS nhập / tổng xuất / tồn (ĐVT phòng)', 'Cần rà', 'KL Bì + Chuẩn', 'KL Bì sau dùng'], $report);

    ksort($stats);
    foreach ($stats as $k => $v) {
        printf("  %-28s %12s\n", $k, $v);
    }

    if ($dry) {
        DB::rollBack();
        echo "DRY RUN - đã rollback. Báo cáo: $dir/3_stock_report.csv\n";
    } else {
        DB::commit();
        echo "ĐÃ GHI. Báo cáo: $dir/3_stock_report.csv\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
