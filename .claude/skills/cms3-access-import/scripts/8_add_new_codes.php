<?php
/**
 * BƯỚC 8 - THÊM MÃ ACCESS MỚI (tạo trên CMS 3.0 sau lần nạp) VÀO DANH MỤC HOÁ CHẤT CÔNG TY, không dựng lại bước 1-2.
 *
 *   - Chỉ xét mã có trong Q_DMHC_ChungHH và đang được bộ phận nào đó dùng (dmhc_dept.json hoặc lots.json),
 *     chưa có mã danh mục (cms3_category_of_access_code).
 *   - Tên hoá chất = TenhoachatTA: có sẵn thì dùng lại; chưa có thì tạo, thành phần = hoạt chất khớp CAS
 *     (mỗi tên phải có >= 1 hoạt chất - không khớp CAS nào thì bỏ qua + báo cáo).
 *   - Gộp như bước 2 theo (tên + NSX + Loại): trùng dòng danh mục sẵn có -> ghi lịch sử "Cập nhật" "gộp thêm mã";
 *     chưa có -> mã C tiếp theo, đã duyệt, cảnh báo an toàn từ cờ Access.
 *   Sau bước này chạy 7_add_departments.php để khai cho phòng rồi 3_stock.php.
 * php 8_add_new_codes.php <thư mục JSON> [--dry]          Ra: <thư mục>/8_add_new_codes_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();
$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];

$used = [];
foreach (cms3_json("$dir/dmhc_dept.json") as $r) {
    $used[$r['Mahoachat']][$r['Bophan']] = 1;
}
foreach (cms3_json("$dir/lots.json") as $l) {
    $used[$l['Mahoachat']][$l['Dept']] = 1;
}
$nsxFull = [];
foreach (cms3_json("$dir/nsx.json") as $n) {
    $nsxFull[mb_strtolower(cms3_clean($n['NSX']))] = cms3_clean($n['Tendaydu']);
}

DB::beginTransaction();
try {
    $catOf = cms3_category_of_access_code();
    $chung = array_values(array_filter(cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")),
        fn ($r) => ! cms3_is_test_row($r) && isset($used[$r['Mahoachat']]) && ! isset($catOf[$r['Mahoachat']])));
    $nextNo = (int) substr((string) DB::table('chemical_categories')->max('code'), 1);

    foreach ($chung as $r) {
        $h = $r['Mahoachat'];
        $name = cms3_clean($r['TenhoachatTA']);
        $line = [$h, $name, implode('/', array_keys($used[$h])), '', '', ''];

        /* Tên hoá chất */
        $chemId = DB::table('chem_names')->where('name', $name)->value('id');
        if (! $chemId) {
            $ais = [];
            foreach (cms3_cas_list($r['CASnumber']) as $cas) {
                if ($ai = DB::table('active_ingredients')->where('cas_no', $cas)->value('id')) {
                    $ais[] = (int) $ai;
                }
            }
            if (! $ais) {
                $line[5] = 'Bỏ qua: không khớp hoạt chất nào theo CAS "' . cms3_clean($r['CASnumber']) . '" - khai tay ở Dữ Liệu Gốc';
                $report[] = $line;
                $inc('skip_no_active_ingredient');
                continue;
            }
            $chemId = DB::table('chem_names')->insertGetId(['name' => mb_substr($name, 0, 255), 'is_special_goods' => 0,
                'is_banned' => ! empty($r['HC_Cam']) ? 1 : 0, 'app_status' => 'approved', 'status_id' => 1,
                'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            foreach (array_unique($ais) as $ai) {
                DB::table('chem_name_active_ingredient')->insert(['chem_names_id' => $chemId, 'active_ingredients_id' => $ai,
                    'content_percent' => null, 'created_at' => $now, 'updated_at' => $now]);
            }
            DataMasterHistory::record('chem_names', $chemId, 'Thêm mới', 'Nạp từ CMS 3.0 (mã ' . $h . '): ' . $name . '.',
                ['name' => 'Tên hoá chất']);
            $inc('chem_names_new');
            $line[3] = 'Tên mới, ' . count(array_unique($ais)) . ' hoạt chất theo CAS';
        }

        /* Nhà sản xuất */
        $short = cms3_nullish($r['NSX']) ?? 'Không rõ';
        $mfr = DB::table('manufacturers')->where('short_name', $short)->value('id');
        if (! $mfr) {
            $full = $nsxFull[mb_strtolower($short)] ?? '';
            $full = $full === '' || DB::table('manufacturers')->where('name', $full)->exists() ? $short : $full;
            $mfr = DB::table('manufacturers')->insertGetId(['short_name' => mb_substr($short, 0, 100), 'name' => $full,
                'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now,
                'created_at' => $now, 'updated_at' => $now]);
            DataMasterHistory::record('manufacturers', $mfr, 'Thêm mới', 'Nạp từ CMS 3.0: ' . $short . ' - ' . $full . '.',
                ['short_name' => 'Tên viết tắt', 'name' => 'Tên nhà sản xuất']);
            $inc('manufacturers_new');
        }
        $type = cms3_nullish($r['Loai']);
        $type = $type === null ? null : mb_substr($type, 0, 30);

        /* Danh mục: gộp với dòng sẵn có cùng (tên + NSX + Loại) */
        $existing = DB::table('chemical_categories')->where('chem_names_id', $chemId)->where('manufacturers_id', $mfr)
            ->where(fn ($q) => $type === null ? $q->whereNull('type') : $q->where('type', $type))->first();
        $warn = cms3_safety_warnings($r);
        if ($existing) {
            $catId = $existing->id;
            $code = $existing->code;
            $action = 'Cập nhật';
            $note = 'CMS 3.0, gộp thêm mã: ' . $h . ' (mã tạo trên CMS 3.0 sau lần nạp đầu).';
            $inc('attached_to_existing');
        } else {
            $code = 'C' . str_pad((string) ++$nextNo, 5, '0', STR_PAD_LEFT);
            $catId = DB::table('chemical_categories')->insertGetId(['code' => $code, 'type' => $type, 'chem_names_id' => $chemId,
                'manufacturers_id' => $mfr, 'storage_condition_id' => null, 'safety_warning' => $warn ? json_encode($warn) : null,
                'app_status' => 'approved', 'status_id' => $r['Active'] ? 1 : 0, 'created_by' => $ACTOR, 'approved_by' => $ACTOR,
                'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now, 'national_portal_declared' => 0]);
            $action = 'Thêm mới';
            $note = 'Nạp từ CMS 3.0, mã cũ: ' . $h . '.';
            $inc('categories_new');
        }
        $cat = DB::table('chemical_categories')->where('id', $catId)->first();
        DB::table('chemical_category_histories')->insert(['chemical_category_id' => $catId, 'action' => $action, 'code' => $cat->code,
            'type' => $cat->type, 'chem_names_id' => $cat->chem_names_id, 'manufacturers_id' => $cat->manufacturers_id,
            'storage_condition_id' => $cat->storage_condition_id, 'classification' => null, 'safety_warning' => $cat->safety_warning,
            'national_portal_declared' => $cat->national_portal_declared, 'app_status' => $cat->app_status, 'status_id' => $cat->status_id,
            'created_by' => $ACTOR, 'created_at' => $now, 'change_note' => $note]);
        $line[4] = $code . ($existing ? ' (gộp vào dòng có sẵn)' : ' (mới)');
        $report[] = $line;
    }

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/8_add_new_codes_report.csv", ['Mã Access', 'Tên (TA)', 'Bộ phận dùng', 'Tên hoá chất', 'Mã danh mục', 'Ghi chú'], $report);
$stats['mode'] = $dry ? 'DRY-RUN (rollback)' : 'COMMITTED';
print_r($stats);
