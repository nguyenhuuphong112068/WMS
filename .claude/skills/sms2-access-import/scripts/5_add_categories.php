<?php
/**
 * BƯỚC 5 - THÊM MÃ SMS 2.0 MỚI (tạo sau lần nạp bước 1) VÀO DANH MỤC CHẤT CHUẨN CÔNG TY, không dựng lại bước 1.
 *
 *   - Chỉ xét mã của Q_DMHC_ChungHH đang được bộ phận dùng (dmhc_dept.json) mà chưa có dòng danh mục
 *     (sms2_codes_from_history).
 *   - Quy tắc gộp như bước 1: (tên chuẩn + nguồn gốc + Ver + nhóm ĐVT). Trùng dòng sẵn có -> lịch sử "Cập nhật"
 *     "Nạp từ SMS 2.0, gộp thêm mã Access: …" (sms2_codes_from_history đọc được); chưa có -> mã S tiếp theo,
 *     bảo quản / nhóm chuẩn / CAS / duyệt như bước 1.
 *   Sau bước này chạy lại bước 2 (danh mục phòng) rồi 3.
 * php 5_add_categories.php <thư mục JSON> [--dry]          Ra: <thư mục>/5_add_categories_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = SMS2['actor'];
$now = now();
$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];

$chung = [];
foreach (sms2_unique_rows(sms2_json("$dir/dmhc_chung.json")) as $r) {
    $chung[$r['Machuan']] = $r;
}
$usedBy = [];
foreach (sms2_json("$dir/dmhc_dept.json") as $r) {
    $usedBy[$r['Machuan']][$r['Bophan']] = 1;
}
$nguongocFull = [];
foreach (sms2_json("$dir/nguongoc.json") as $n) {
    $nguongocFull[mb_strtolower(sms2_clean($n['NSX']))] = sms2_clean($n['TenDayDu']);
}
$master = fn (array $extra) => $extra + ['app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR,
    'approved_by' => $ACTOR, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now];

DB::beginTransaction();
try {
    $catOf = sms2_codes_from_history('standard_category_histories', 'standard_category_id');

    // Khoá gộp của các dòng danh mục sẵn có: tên + NSX + Ver + nhóm ĐVT (ĐVT lấy từ mã Access đã gộp vào dòng)
    $cats = DB::table('standard_categories')->get(['id', 'chem_names_id', 'manufacturers_id', 'version'])->keyBy('id');
    $keyOfCat = [];
    foreach ($catOf as $code => $catId) {
        if (isset($chung[$code], $cats[$catId])) {
            $c = $cats[$catId];
            $keyOfCat[$c->chem_names_id . '|' . $c->manufacturers_id . '|' . (int) $c->version . '|'
                . sms2_unit_key(sms2_unit_id($chung[$code]['DVT']))] = $catId;
        }
    }

    $storageOf = [];
    foreach (SMS2['storage_map'] as $name => $keys) {
        $id = DB::table('storage_conditions')->where('name', $name)->value('id');
        foreach ($keys as $k) {
            $storageOf[$k] = $id ? (int) $id : null;
        }
    }
    $groupKeys = array_keys(config('standard.groups'));
    $nextNo = (int) substr((string) DB::table('standard_categories')->max('code'), 1);

    $groups = [];
    foreach ($chung as $code => $r) {
        if (! isset($usedBy[$code]) || isset($catOf[$code])) {
            continue;
        }
        $name = sms2_clean($r['Tenchuan']);
        $nid = DB::table('standard_names')->where('name', $name)->value('id');
        if (! $nid) {
            $nid = DB::table('standard_names')->insertGetId($master(['name' => mb_substr($name, 0, 255),
                'cas_no' => sms2_nullish($r['CasNo']) ? mb_substr(sms2_clean($r['CasNo']), 0, 100) : null]));
            DataMasterHistory::record('standard_names', $nid, 'Thêm mới', 'Nạp từ SMS 2.0, mã Access: ' . $code . '.',
                ['name' => 'Tên chuẩn', 'active_ingredient_name' => 'Tên hoạt chất', 'cas_no' => 'Số CAS']);
            $inc('standard_names_new');
        }
        $short = sms2_nullish($r['Nguongoc']) ?? 'Không rõ';
        $mfr = DB::table('manufacturers')->where('short_name', $short)->value('id');
        if (! $mfr) {
            $full = $nguongocFull[mb_strtolower($short)] ?? '';
            $full = $full === '' ? $short : $full;
            if (DB::table('manufacturers')->where('name', $full)->exists()) {
                $full = $full === $short ? "$short (SMS 2.0)" : "$full ($short)";
            }
            $mfr = DB::table('manufacturers')->insertGetId($master(['short_name' => mb_substr($short, 0, 100), 'name' => mb_substr($full, 0, 255)]));
            DataMasterHistory::record('manufacturers', $mfr, 'Thêm mới', 'Nạp từ SMS 2.0 (Nguồn gốc chất chuẩn): ' . $short . ' - ' . $full . '.',
                ['short_name' => 'Tên viết tắt', 'name' => 'Tên nhà sản xuất']);
            $inc('manufacturers_new');
        }
        $key = $nid . '|' . $mfr . '|' . (int) $r['Ver'] . '|' . sms2_unit_key(sms2_unit_id($r['DVT']));
        $groups[$key][] = $r + ['_name' => (int) $nid, '_mfr' => (int) $mfr];
    }

    $depts = fn ($x) => implode('/', array_keys(array_filter(['QC' => $x['QC'], 'QC1' => $x['QC1'], 'QC2' => $x['QC2'], 'AD' => $x['AD']]))) ?: '-';
    $detail = fn ($list) => implode(', ', array_map(fn ($x) => $x['Machuan'] . ' (ĐVT ' . (sms2_clean($x['DVT']) ?: '-') . '; phòng ' . $depts($x)
        . '; tạo ' . (sms2_clean($x['NguoiTao']) ?: '-') . ' ' . ($x['NgayTao'] ?? '')
        . '; duyệt ' . (sms2_clean($x['Approver']) ?: '-') . ' ' . ($x['NgayApp'] ?? '') . ')', $list));

    foreach ($groups as $key => $list) {
        $r = $list[0];
        $codes = implode(', ', array_column($list, 'Machuan'));
        if (isset($keyOfCat[$key])) {
            $catId = $keyOfCat[$key];
            $cat = (array) DB::table('standard_categories')->where('id', $catId)->first();
            $payload = array_intersect_key($cat, array_flip(['code', 'chem_names_id', 'cas_no', 'manufacturers_id', 'density',
                'storage_condition_id', 'version', 'groups', 'shelf_life_months', 'doc_no', 'note', 'lead_time_days', 'app_status', 'status_id']));
            DB::table('standard_category_histories')->insert(['standard_category_id' => $catId, 'action' => 'Cập nhật'] + $payload
                + ['change_note' => 'Nạp từ SMS 2.0, gộp thêm mã Access: ' . $detail($list) . '.', 'created_by' => $ACTOR, 'created_at' => $now]);
            $inc('attached_to_existing', count($list));
            $report[] = [$codes, sms2_clean($r['Tenchuan']), $r['Nguongoc'], (int) $r['Ver'], sms2_clean($r['DVT']), $cat['code'], 'Gộp vào dòng có sẵn'];
            continue;
        }

        $flags = [];
        foreach ($list as $x) {
            foreach (SMS2['group_flags'] as $f => $g) {
                if (! empty($x[$f])) {
                    $flags[$g] = 1;
                }
            }
        }
        $stdGroups = array_values(array_intersect($groupKeys, array_keys($flags)));
        $raw = sms2_nullish($r['BaoQuan']);
        $storageId = $raw === null ? null : ($storageOf[sms2_storage_key($raw)] ?? null);
        $approved = array_reduce($list, fn ($c, $x) => $c && ! empty($x['Approved']), true);
        $cas = sms2_mode(array_map(fn ($x) => sms2_nullish($x['CasNo']), $list));
        $dates = array_filter(array_column($list, 'NgayTao'));
        $code = 'S' . str_pad((string) ++$nextNo, 5, '0', STR_PAD_LEFT);
        $payload = ['code' => $code, 'chem_names_id' => $r['_name'], 'cas_no' => $cas === null ? null : mb_substr($cas, 0, 50),
            'manufacturers_id' => $r['_mfr'], 'density' => null, 'storage_condition_id' => $storageId, 'version' => (int) $r['Ver'],
            'groups' => $stdGroups ? json_encode($stdGroups, JSON_UNESCAPED_UNICODE) : null, 'shelf_life_months' => null,
            'doc_no' => null, 'note' => $raw !== null && ($storageId === null || $raw !== DB::table('storage_conditions')->where('id', $storageId)->value('name'))
                ? mb_substr('Điều kiện bảo quản gốc (SMS 2.0): ' . $raw, 0, 500) : null,
            'lead_time_days' => null, 'app_status' => $approved ? 'approved' : 'pending', 'status_id' => 1];
        $id = DB::table('standard_categories')->insertGetId($payload + ['created_by' => $ACTOR,
            'approved_by' => $approved ? $ACTOR : null, 'approved_at' => $approved ? $now : null,
            'created_at' => $dates ? min($dates) . ' 00:00:00' : $now, 'updated_at' => $now]);
        DB::table('standard_category_histories')->insert(['standard_category_id' => $id, 'action' => 'Thêm mới'] + $payload
            + ['change_note' => 'Nạp từ SMS 2.0, mã Access: ' . $detail($list) . '.', 'created_by' => $ACTOR, 'created_at' => $now]);
        $keyOfCat[$key] = $id;
        $inc('categories_new');
        $report[] = [$codes, sms2_clean($r['Tenchuan']), $r['Nguongoc'], (int) $r['Ver'], sms2_clean($r['DVT']), $code,
            'Mã mới' . ($stdGroups ? '' : ' - KHÔNG có nhóm chuẩn') . ($approved ? '' : ' - chờ duyệt')
            . ($raw !== null && $storageId === null ? ' - bảo quản chưa ánh xạ: ' . $raw : '')];
    }

    sms2_write_csv("$dir/5_add_categories_report.csv", ['Mã Access', 'Tên chuẩn', 'Nguồn gốc', 'Ver', 'ĐVT', 'Mã WMS', 'Ghi chú'], $report);
    ksort($stats);
    foreach ($stats as $k => $v) {
        printf("  %-32s %6d\n", $k, $v);
    }
    if ($dry) {
        DB::rollBack();
        echo "DRY RUN - đã rollback.\n";
    } else {
        DB::commit();
        echo "ĐÃ GHI.\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
