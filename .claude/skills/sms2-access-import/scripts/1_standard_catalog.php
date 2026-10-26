<?php
/**
 * DANH MỤC CHẤT CHUẨN CÔNG TY + DỮ LIỆU GỐC LIÊN QUAN, từ Q_DMHC_ChungHH (SMS 2.0).
 *
 *   - standard_names      : Tenchuan (gộp theo collation CSDL: khác hoa/thường, dấu => 1 tên); cas_no = CAS khác NA.
 *   - manufacturers       : Nguongoc -> dùng lại NSX có sẵn theo short_name, chưa có thì tạo (tên đầy đủ từ dbo_T_Nguongoc).
 *   - storage_conditions  : BaoQuan CHUẨN HOÁ về các nhóm ở SMS2['storage_map']; chuỗi gốc ghi vào note danh mục.
 *   - units               : bổ sung ĐVT Access còn thiếu (đơn vị nằm ở danh mục PHÒNG, ở đây chỉ chuẩn bị sẵn).
 *   - standard_categories : GỘP theo (tên chuẩn + nguồn gốc + Ver + nhóm ĐVT, xem sms2_unit_key) - người dùng
 *     chốt 26/10/2026 (thêm nhóm ĐVT để mỗi phòng chỉ có 1 ĐVT cho 1 dòng danh mục); mã S00001.. liên tục
 *     theo thứ tự mã Access nhỏ nhất của nhóm; version = Ver Access; nhóm chuẩn = hợp cờ API/IMPU/CTC/VKN/CN/CHH/CNL;
 *     đã duyệt nếu mọi mã gộp đều Approved ở Access, còn lại Chờ duyệt. Mã Access gộp vào ghi ở lịch sử "Thêm mới".
 *
 * CHẠY LẠI = xoá phần do script tạo (created_by = SMS2['actor']) rồi dựng lại; tự dừng nếu danh mục đã được
 * phòng khai / nhập kho / dự trù.
 * php 1_standard_catalog.php <thư mục JSON> [--dry]          Ra: <thư mục>/1_standard_catalog_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = SMS2['actor'];
$now = now();

$rows = sms2_unique_rows(sms2_json("$dir/dmhc_chung.json"));
$nguongocFull = [];
foreach (sms2_json("$dir/nguongoc.json") as $n) {
    $nguongocFull[mb_strtolower(sms2_clean($n['NSX']))] = sms2_clean($n['TenDayDu']);
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };

/** Bảng nào (trừ *_histories) còn trỏ tới id này qua cột $column. */
function sms2_used(string $column, int $id): bool
{
    static $tables = [];
    $tables[$column] ??= DB::table('information_schema.columns')->where('table_schema', DB::getDatabaseName())
        ->where('column_name', $column)->where('table_name', 'not like', '%histories')->pluck('table_name')->all();
    foreach ($tables[$column] as $t) {
        if (DB::table($t)->where($column, $id)->exists()) {
            return true;
        }
    }
    return false;
}

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu script tạo trước đó ---------- */
    $oldCats = DB::table('standard_categories')->where('created_by', $ACTOR)->pluck('id');
    // Danh mục phòng do bước 2 tạo đi theo danh mục công ty -> xoá cùng (chạy lại bước 1 thì chạy lại bước 2)
    sms2_delete_stock($argv, $inc);
    $inc('cleanup_department_categories', DB::table('standard_department_categories')->where('created_by', $ACTOR)
        ->whereIn('category_id', $oldCats)->delete());
    foreach (['standard_department_categories', 'standard_imports', 'standard_estimate_items'] as $t) {
        if (DB::table($t)->whereIn('category_id', $oldCats)->exists() && ! in_array('--force', $argv, true)) {
            throw new RuntimeException("Danh mục chất chuẩn đã nạp đang được dùng ở $t - không xoá. Thêm --force nếu chắc chắn.");
        }
    }
    if (DB::table('standard_categories')->where('created_by', '<>', $ACTOR)->exists()) {
        throw new RuntimeException('Đã có danh mục chất chuẩn do người dùng tạo - mã S00001.. sẽ trùng. Dừng.');
    }
    DB::table('standard_category_histories')->whereIn('standard_category_id', $oldCats)->delete();
    DB::table('standard_categories')->whereIn('id', $oldCats)->delete();
    $inc('cleanup_categories', count($oldCats));

    foreach (['standard_names' => 'chem_names_id', 'storage_conditions' => 'storage_condition_id',
              'manufacturers' => 'manufacturers_id', 'units' => 'unit_id'] as $table => $column) {
        foreach (DB::table($table)->where('created_by', $ACTOR)->pluck('id') as $id) {
            // standard_names được trỏ bằng cột chem_names_id (dùng chung tên cột với hoá chất) -> chỉ xét bảng chuẩn
            $used = $table === 'standard_names'
                ? DB::table('standard_categories')->where('chem_names_id', $id)->exists()
                : sms2_used($column, (int) $id);
            if ($used) {
                continue;
            }
            DB::table('datamaster_histories')->where('table_name', $table)->where('record_id', $id)->delete();
            DB::table($table)->where('id', $id)->delete();
            $inc("cleanup_$table");
        }
    }

    $master = fn (array $extra) => $extra + ['app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR,
        'approved_by' => $ACTOR, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now];

    /* ---------- 1. Điều kiện bảo quản ---------- */
    $storageOf = [];   // khoá chuỗi Access => id
    $storageName = []; // id => tên
    foreach (SMS2['storage_map'] as $name => $keys) {
        $id = DB::table('storage_conditions')->where('name', $name)->value('id');
        if (! $id) {
            $id = DB::table('storage_conditions')->insertGetId($master(['name' => $name,
                'note' => 'Nhóm chuẩn hoá điều kiện bảo quản chất chuẩn SMS 2.0']));
            DataMasterHistory::record('storage_conditions', $id, 'Thêm mới', 'Nạp từ SMS 2.0: nhóm bảo quản ' . $name . '.',
                ['name' => 'Tên điều kiện bảo quản', 'note' => 'Ghi chú']);
            $inc('storage_conditions_new');
        }
        $storageName[$id] = $name;
        foreach ($keys as $k) {
            $storageOf[$k] = (int) $id;
        }
    }

    /* ---------- 2. Đơn vị (chuẩn bị cho danh mục phòng) ---------- */
    foreach (array_unique(array_map(fn ($r) => mb_strtolower(sms2_clean($r['DVT'])), $rows)) as $dvt) {
        if ($dvt === '' || DB::table('units')->where('short_name', $dvt)->exists()) {
            continue;
        }
        $def = SMS2['units'][$dvt] ?? null;
        if (! is_array($def)) {
            if (! $def || ! DB::table('units')->where('short_name', $def)->exists()) {
                $inc('units_unmapped');
                echo "  ! ĐVT chưa ánh xạ: $dvt\n";
            }
            continue;
        }
        $id = DB::table('units')->insertGetId($master(['short_name' => $def[0], 'name' => $def[1],
            'unit_group' => 'count', 'factor_to_base' => 1]));
        DataMasterHistory::record('units', $id, 'Thêm mới', 'Nạp từ SMS 2.0: đơn vị ' . $def[1] . '.',
            ['short_name' => 'Tên viết tắt', 'name' => 'Tên đơn vị']);
        $inc('units_new');
    }

    /* ---------- 3. Tên chuẩn + nguồn gốc, gom nhóm danh mục ---------- */
    $nameId = [];
    $nameCodes = [];
    $nameCas = [];
    $mfrId = [];
    $groups = [];
    foreach ($rows as $r) {
        $name = sms2_clean($r['Tenchuan']);
        if ($name === '') {
            $inc('rows_skip_no_name');
            continue;
        }
        if (! isset($nameId[$name])) {
            $id = DB::table('standard_names')->where('name', $name)->value('id');
            if (! $id) {
                $id = DB::table('standard_names')->insertGetId($master(['name' => mb_substr($name, 0, 255)]));
                $inc('standard_names_new');
            }
            $nameId[$name] = (int) $id;
        }
        $nid = $nameId[$name];
        $nameCodes[$nid][] = $r['Machuan'];
        if ($cas = sms2_nullish($r['CasNo'])) {
            $nameCas[$nid][] = $cas;
        }

        $short = sms2_nullish($r['Nguongoc']) ?? 'Không rõ';
        $k = mb_strtolower($short);
        if (! isset($mfrId[$k])) {
            $id = DB::table('manufacturers')->where('short_name', $short)->value('id');
            if (! $id) {
                $full = $nguongocFull[$k] ?? '';
                $full = $full === '' ? $short : $full;
                if (DB::table('manufacturers')->where('name', $full)->exists()) {
                    $full = $full === $short ? "$short (SMS 2.0)" : "$full ($short)";
                }
                $id = DB::table('manufacturers')->insertGetId($master(['short_name' => mb_substr($short, 0, 100),
                    'name' => mb_substr($full, 0, 255)]));
                DataMasterHistory::record('manufacturers', $id, 'Thêm mới', 'Nạp từ SMS 2.0 (Nguồn gốc chất chuẩn): '
                    . $short . ' - ' . $full . '.', ['short_name' => 'Tên viết tắt', 'name' => 'Tên nhà sản xuất']);
                $inc('manufacturers_new');
            }
            $mfrId[$k] = (int) $id;
        }

        $unitKey = sms2_unit_key(sms2_unit_id($r['DVT']));
        $groups[$nid . '|' . $mfrId[$k] . '|' . (int) $r['Ver'] . '|' . $unitKey][] = $r + ['_name' => $nid, '_mfr' => $mfrId[$k]];
    }

    // CAS của tên chuẩn + lịch sử "Thêm mới" (sau khi đã biết đủ mã Access dùng tên đó)
    $created = DB::table('standard_names')->where('created_by', $ACTOR)->pluck('id')->flip();
    foreach ($nameId as $nid) {
        if (! isset($created[$nid])) {
            continue;
        }
        $cas = array_values(array_unique($nameCas[$nid] ?? []));
        $casText = $cas ? mb_substr(implode(', ', $cas), 0, 100) : null;
        DB::table('standard_names')->where('id', $nid)->update(['cas_no' => $casText]);
        if (! DB::table('datamaster_histories')->where('table_name', 'standard_names')->where('record_id', $nid)->exists()) {
            DataMasterHistory::record('standard_names', $nid, 'Thêm mới', 'Nạp từ SMS 2.0, mã Access: '
                . implode(', ', array_unique($nameCodes[$nid])) . '.',
                ['name' => 'Tên chuẩn', 'active_ingredient_name' => 'Tên hoạt chất', 'cas_no' => 'Số CAS']);
        }
    }

    /* ---------- 4. Danh mục chất chuẩn công ty ---------- */
    $groupKeys = array_keys(config('standard.groups'));
    $mfrShort = DB::table('manufacturers')->pluck('short_name', 'id');
    $report = [];
    $n = 0;
    foreach ($groups as $list) {
        $r = $list[0];
        $issues = [];

        $flags = [];
        foreach ($list as $x) {
            foreach (SMS2['group_flags'] as $f => $key) {
                if (! empty($x[$f])) {
                    $flags[$key][] = $x['Machuan'];
                }
            }
        }
        $stdGroups = array_values(array_intersect($groupKeys, array_keys($flags)));
        if (count($stdGroups) > 1) {
            $issues[] = 'Nhiều nhóm chuẩn';
        }
        if (! $stdGroups) {
            $issues[] = 'Không có nhóm chuẩn (không nhập kho được)';
        }

        $rawStorage = [];
        $mapped = [];
        foreach ($list as $x) {
            $raw = sms2_nullish($x['BaoQuan']);
            if ($raw === null) {
                continue;
            }
            $rawStorage[$raw] = 1;
            $sid = $storageOf[sms2_storage_key($raw)] ?? null;
            if ($sid === null) {
                $issues[] = 'Bảo quản chưa ánh xạ: ' . $raw;
                $inc('storage_unmapped_rows');
            }
            $mapped[] = $sid;
        }
        $storageId = sms2_mode($mapped);
        $storageId = $storageId === null ? null : (int) $storageId;
        if (count(array_unique(array_filter($mapped))) > 1) {
            $issues[] = 'Bảo quản lệch giữa các mã gộp';
        }
        $rawStorage = array_keys($rawStorage);
        $note = null;
        if ($rawStorage && ! (count($rawStorage) === 1 && $storageId && $rawStorage[0] === $storageName[$storageId])) {
            $note = mb_substr('Điều kiện bảo quản gốc (SMS 2.0): ' . implode('; ', $rawStorage), 0, 500);
        }

        $cas = sms2_mode(array_map(fn ($x) => sms2_nullish($x['CasNo']), $list));
        if ($cas === null) {
            $issues[] = 'Không có CAS';
        } elseif (mb_strlen($cas) > 50) {
            $issues[] = 'CAS dài > 50 ký tự (đã cắt)';
            $cas = mb_substr($cas, 0, 50);
        }

        $approved = array_reduce($list, fn ($c, $x) => $c && ! empty($x['Approved']), true);
        if (! $approved) {
            $issues[] = 'Chưa duyệt ở SMS 2.0';
        }
        $dates = array_filter(array_column($list, 'NgayTao'));
        $createdAt = $dates ? min($dates) . ' 00:00:00' : $now;

        $code = 'S' . str_pad((string) ++$n, 5, '0', STR_PAD_LEFT);
        $payload = ['code' => $code, 'chem_names_id' => $r['_name'], 'cas_no' => $cas, 'manufacturers_id' => $r['_mfr'],
            'density' => null, 'storage_condition_id' => $storageId, 'version' => (int) $r['Ver'],
            'groups' => $stdGroups ? json_encode($stdGroups, JSON_UNESCAPED_UNICODE) : null,
            'shelf_life_months' => null, 'doc_no' => null, 'note' => $note, 'lead_time_days' => null,
            'app_status' => $approved ? 'approved' : 'pending', 'status_id' => 1];
        $id = DB::table('standard_categories')->insertGetId($payload + ['created_by' => $ACTOR,
            'approved_by' => $approved ? $ACTOR : null, 'approved_at' => $approved ? $now : null,
            'created_at' => $createdAt, 'updated_at' => $now]);

        $depts = fn ($x) => implode('/', array_keys(array_filter(['QC' => $x['QC'], 'QC1' => $x['QC1'], 'QC2' => $x['QC2'], 'AD' => $x['AD']]))) ?: '-';
        $detail = array_map(fn ($x) => $x['Machuan'] . ' (ĐVT ' . (sms2_clean($x['DVT']) ?: '-') . '; phòng ' . $depts($x)
            . '; tạo ' . (sms2_clean($x['NguoiTao']) ?: '-') . ' ' . ($x['NgayTao'] ?? '')
            . '; duyệt ' . (sms2_clean($x['Approver']) ?: '-') . ' ' . ($x['NgayApp'] ?? '') . ')', $list);
        DB::table('standard_category_histories')->insert(['standard_category_id' => $id, 'action' => 'Thêm mới']
            + $payload + ['change_note' => 'Nạp từ SMS 2.0, mã Access: ' . implode(', ', $detail) . '.',
            'created_by' => $ACTOR, 'created_at' => $now]);

        $inc('categories_new');
        $inc('categories_merged_codes', count($list));
        if (count($list) > 1) {
            $issues[] = 'Gộp ' . count($list) . ' mã';
            $inc('categories_with_merge');
        }
        $inc($approved ? 'categories_approved' : 'categories_pending');

        $report[] = [$code, sms2_clean($r['Tenchuan']), $mfrShort[$r['_mfr']] ?? '', (int) $r['Ver'],
            implode(', ', $stdGroups), $storageId ? $storageName[$storageId] : '', implode('; ', $rawStorage), $cas,
            implode(', ', array_column($list, 'Machuan')),
            implode(', ', array_unique(array_map(fn ($x) => sms2_clean($x['DVT']), $list))),
            implode(', ', array_unique(array_map($depts, $list))),
            $approved ? 'Đã duyệt' : 'Chờ duyệt', implode(' | ', array_unique($issues))];
    }

    sms2_write_csv("$dir/1_standard_catalog_report.csv",
        ['Mã WMS', 'Tên chuẩn', 'Nguồn gốc', 'Version', 'Nhóm chuẩn', 'Bảo quản WMS', 'Bảo quản gốc', 'CAS',
         'Mã Access', 'ĐVT Access', 'Phòng dùng (Access)', 'Trạng thái', 'Cần rà'], $report);

    $stats['access_rows_unique'] = count($rows);
    ksort($stats);
    foreach ($stats as $k => $v) {
        printf("  %-32s %6d\n", $k, $v);
    }

    if ($dry) {
        DB::rollBack();
        echo "DRY RUN - đã rollback. Báo cáo: $dir/1_standard_catalog_report.csv\n";
    } else {
        DB::commit();
        echo "ĐÃ GHI. Báo cáo: $dir/1_standard_catalog_report.csv\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
