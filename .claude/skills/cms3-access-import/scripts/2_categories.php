<?php
/**
 * BƯỚC 2 - DANH MỤC HOÁ CHẤT CÔNG TY + ĐỊNH KHU + DANH MỤC HOÁ CHẤT CỦA PHÒNG.
 *
 *   - chemical_categories: từ dmhc_chung.json, gộp theo (tên hoá chất WMS + NSX + Loại); mã C00001.. liên tục;
 *     đã duyệt; Active=False -> status_id 0; cảnh báo an toàn từ Doc/DeChay/AnMon/DeNo/Oxyhoa; mã Access cũ
 *     ghi ở lịch sử "Thêm mới" (bước 3 đọc lại từ đây).
 *   - Tên hoá chất WMS của từng mã Access: đọc từ lịch sử "Thêm mới" của chem_names (bước 1).
 *   - Định khu dựng theo mã Dinhkhu Access: "14D4" -> Kho/Tủ 14/Cột 01/Tầng D/Vị trí 04 (item_type=chemical).
 *     Không tự xếp lại theo tương kỵ - user chọn dựng đúng như Access; vi phạm liệt kê ở báo cáo.
 *   - chemical_department_categories: từ dmhc_dept.json; ĐVT theo DVT (tạo đơn vị đếm còn thiếu);
 *     shelf_life_months = SNHD (số THÁNG hạn dùng sau khi mở nắp, 0 -> trống);
 *     min/max_stock NGẪU NHIÊN (Access không có ngưỡng).
 *
 * CHẠY LẠI = xoá danh mục công ty, định khu, danh mục phòng, phiếu nhập do bộ script tạo rồi dựng lại.
 * php 2_categories.php <thư mục JSON> [--dry]          Ra: <thư mục>/2_categories_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\ChemicalCompatibility;
use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();
mt_srand(CMS3['random_seed']);

/** Mã Dinhkhu -> [tủ, tầng, vị trí] hoặc null (không đặt định khu). */
function parseLocation(string $raw): ?array
{
    $d = mb_strtoupper(cms3_clean($raw));
    if ($d === '' || in_array($d, ['NA', 'N/A', '-'], true) || preg_match('/^OQDK\d+$/', $d)) {
        return null;
    }
    if (preg_match('/^TỦ\s*(\S+)$/u', $d, $m)) {
        return [$m[1], '1', '1'];
    }
    if (preg_match('/^([A-Z]*\d+)([A-Z]+)(\d+)$/', $d, $m)) {   // 14D4, T1H5, 9AA1
        return [$m[1], $m[2], $m[3]];
    }
    if (preg_match('/^(\d+)([A-Z]+)$/', $d, $m)) {              // 15A, 381B
        return [$m[1], $m[2], '1'];
    }
    if (preg_match('/^([A-Z]*\d+)\.([A-Z]*)(\d+)$/', $d, $m)) { // 5.1, DTT1.2, 034.X1
        return $m[2] === '' ? [$m[1], $m[3], '1'] : [$m[1], $m[2], $m[3]];
    }
    return [$d, '1', '1'];                                       // DT, DC, M5, T2DDCĐ -> cả mã là 1 tủ
}

function tierPosition(string $t): int
{
    if (ctype_digit($t)) {
        return (int) $t;
    }
    $n = 0;
    foreach (str_split(preg_replace('/[^A-Z]/', '', $t)) as $ch) {
        $n = $n * 26 + (ord($ch) - 64);
    }
    return $n ?: 1;
}

$pad = fn ($v) => ctype_digit((string) $v) ? str_pad((string) (int) $v, 2, '0', STR_PAD_LEFT) : (string) $v;

$chung = array_values(array_filter(cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")), fn ($r) => ! cms3_is_test_row($r)));
$deptRows = cms3_json("$dir/dmhc_dept.json");
$nsxFull = [];
foreach (cms3_json("$dir/nsx.json") as $n) {
    $nsxFull[mb_strtolower(cms3_clean($n['NSX']))] = cms3_clean($n['Tendaydu']);
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu bộ script tạo trước đó ---------- */
    if (DB::table('chemical_exports')->count() || DB::table('chemical_imports')->where('created_by', '<>', $ACTOR)->count()) {
        if (! in_array('--force', $argv, true)) {
            throw new RuntimeException('Đã có phiếu nhập/xuất hoá chất thật - không xoá danh mục. Thêm --force nếu chắc chắn.');
        }
    }
    $imp = DB::table('chemical_imports')->where('created_by', $ACTOR)->pluck('id');
    DB::table('chemical_import_histories')->whereIn('import_id', $imp)->delete();
    DB::table('chemical_imports')->whereIn('id', $imp)->delete();
    DB::table('chemical_department_categories')->where('created_by', $ACTOR)->delete();
    DB::table('chemical_category_histories')->delete();
    DB::table('chemical_categories')->delete();
    foreach (['locations', 'tiers', 'columns', 'shelves', 'warehouses'] as $t) {
        DB::table($t)->where('created_by', $ACTOR)->delete();
    }

    /* ---------- 1. Danh mục hoá chất công ty ---------- */
    $chemOf = cms3_codes_from_history('datamaster_histories', 'record_id', 'change_note', ['table_name' => 'chem_names']);
    $mfr = [];
    foreach (DB::table('manufacturers')->get(['id', 'short_name']) as $m) {
        $mfr[mb_strtolower($m->short_name)] = (int) $m->id;
    }
    $usedMfrNames = DB::table('manufacturers')->pluck('name')->map(fn ($n) => mb_strtolower($n))->flip()->all();
    $groups = [];
    foreach ($chung as $r) {
        $chemId = $chemOf[$r['Mahoachat']] ?? null;
        if (! $chemId) {
            $inc('chung_skip_no_chem_name');
            continue;
        }
        $short = cms3_nullish($r['NSX']) ?? 'Không rõ';
        $k = mb_strtolower($short);
        if (! isset($mfr[$k])) {
            $full = $nsxFull[$k] ?? '';
            if ($full === '' || isset($usedMfrNames[mb_strtolower($full)])) {
                $full = $short;
            }
            $usedMfrNames[mb_strtolower($full)] = 1;
            $mfr[$k] = DB::table('manufacturers')->insertGetId(['short_name' => mb_substr($short, 0, 100), 'name' => $full,
                'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now,
                'created_at' => $now, 'updated_at' => $now]);
            DataMasterHistory::record('manufacturers', $mfr[$k], 'Thêm mới', 'Nạp từ CMS 3.0: ' . $short . ' - ' . $full . '.',
                ['short_name' => 'Tên viết tắt', 'name' => 'Tên nhà sản xuất']);
            $inc('manufacturers_new');
        }
        $type = cms3_nullish($r['Loai']);
        $type = $type === null ? null : mb_substr($type, 0, 30);
        $groups[$chemId . '|' . $mfr[$k] . '|' . mb_strtolower((string) $type)][] = $r + ['_chem' => $chemId, '_mfr' => $mfr[$k], '_type' => $type];
    }
    $catOf = [];
    $catCode = [];
    $catWarn = [];
    $n = 0;
    foreach ($groups as $list) {
        $r = $list[0];
        $active = array_reduce($list, fn ($c, $x) => $c || $x['Active'], false);
        $code = 'C' . str_pad((string) ++$n, 5, '0', STR_PAD_LEFT);
        $warn = cms3_safety_warnings($r);
        $id = DB::table('chemical_categories')->insertGetId(['code' => $code, 'type' => $r['_type'], 'chem_names_id' => $r['_chem'],
            'manufacturers_id' => $r['_mfr'], 'storage_condition_id' => null, 'safety_warning' => $warn ? json_encode($warn) : null,
            'app_status' => 'approved', 'status_id' => $active ? 1 : 0, 'created_by' => $ACTOR, 'approved_by' => $ACTOR,
            'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('chemical_category_histories')->insert(['chemical_category_id' => $id, 'action' => 'Thêm mới', 'code' => $code,
            'type' => $r['_type'], 'chem_names_id' => $r['_chem'], 'manufacturers_id' => $r['_mfr'], 'storage_condition_id' => null,
            'classification' => null, 'safety_warning' => $warn ? json_encode($warn) : null, 'national_portal_declared' => 0,
            'app_status' => 'approved', 'status_id' => $active ? 1 : 0, 'created_by' => $ACTOR, 'created_at' => $now,
            'change_note' => 'Nạp từ CMS 3.0, mã cũ: ' . implode(', ', array_map(fn ($x) => $x['Mahoachat'], $list)) . '.']);
        foreach ($list as $x) {
            $catOf[$x['Mahoachat']] = $id;
        }
        $catCode[$id] = $code;
        $catWarn[$id] = $warn;
        $inc('categories_new');
    }

    /* ---------- 2. Đơn vị ---------- */
    $unitId = [];
    foreach (DB::table('units')->get(['id', 'short_name']) as $u) {
        $unitId[mb_strtolower($u->short_name)] = (int) $u->id;
    }
    $resolveUnit = function (string $dvt) use (&$unitId, $ACTOR, $now, $inc) {
        $k = mb_strtolower(cms3_clean($dvt));
        if (isset($unitId[$k])) {
            return $unitId[$k];
        }
        if (! isset(CMS3['new_units'][$k])) {
            return null;
        }
        [$short, $name] = CMS3['new_units'][$k];
        $id = DB::table('units')->insertGetId(['short_name' => $short, 'name' => $name, 'unit_group' => 'count', 'factor_to_base' => 1,
            'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now,
            'created_at' => $now, 'updated_at' => $now]);
        DataMasterHistory::record('units', $id, 'Thêm mới', 'Nạp từ CMS 3.0: đơn vị ' . $name . '.', ['short_name' => 'Tên viết tắt', 'name' => 'Tên đơn vị']);
        $inc('units_new');
        return $unitId[$k] = $id;
    };

    /* ---------- 3. Định khu theo Dinhkhu ---------- */
    $ids = ['wh' => [], 'shelf' => [], 'col' => [], 'tier' => [], 'loc' => []];
    $base = fn ($dept) => ['department_id' => $dept, 'status_id' => 1, 'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now];
    $locationFor = function (string $bp, array $p) use (&$ids, $base, $pad, $inc) {
        [$shelf, $tier, $pos] = $p;
        $cfg = CMS3['departments'][$bp];
        $dept = $cfg['id'];
        $whCode = $cfg['warehouse_code'];
        if (! isset($ids['wh'][$bp])) {
            $ids['wh'][$bp] = DB::table('warehouses')->insertGetId($base($dept) + ['code' => $whCode, 'name' => $cfg['warehouse_name']]);
            DataMasterHistory::record('warehouses', $ids['wh'][$bp], 'Thêm mới', 'Nạp định khu hoá chất từ CMS 3.0 (cột Dinhkhu).', ['code' => 'Mã', 'name' => 'Tên']);
            $inc('warehouses_new');
        }
        $wh = $ids['wh'][$bp];
        $sCode = $whCode . '/' . $shelf;
        if (! isset($ids['shelf'][$sCode])) {
            $ids['shelf'][$sCode] = DB::table('shelves')->insertGetId($base($dept) + ['code' => $sCode, 'name' => 'Tủ ' . $shelf, 'warehouse_id' => $wh]);
            $ids['col'][$sCode] = DB::table('columns')->insertGetId($base($dept) + ['code' => $sCode . '/01', 'name' => 'Cột 01',
                'warehouse_id' => $wh, 'shelf_id' => $ids['shelf'][$sCode], 'position' => 1]);
            $inc('shelves_new');
        }
        $tCode = $sCode . '/01/' . $pad($tier);
        if (! isset($ids['tier'][$tCode])) {
            $ids['tier'][$tCode] = DB::table('tiers')->insertGetId($base($dept) + ['code' => $tCode, 'name' => 'Tầng ' . $pad($tier),
                'warehouse_id' => $wh, 'shelf_id' => $ids['shelf'][$sCode], 'column_id' => $ids['col'][$sCode], 'position' => tierPosition($tier)]);
            $inc('tiers_new');
        }
        $lCode = $tCode . '/' . $pad($pos);
        if (! isset($ids['loc'][$lCode])) {
            $ids['loc'][$lCode] = DB::table('locations')->insertGetId($base($dept) + ['code' => $lCode, 'warehouse_id' => $wh,
                'shelf_id' => $ids['shelf'][$sCode], 'column_id' => $ids['col'][$sCode], 'tier_id' => $ids['tier'][$tCode],
                'position' => (int) $pos, 'item_type' => 'chemical', 'zone_type' => null]);
            $inc('locations_new');
        }
        return [$ids['loc'][$lCode], $lCode, $ids['tier'][$tCode]];
    };

    /* ---------- 4. Danh mục hoá chất của phòng ---------- */
    $unitShort = DB::table('units')->pluck('short_name', 'id')->all();
    $randStock = function (int $unit) use (&$unitShort) {
        [$lo, $hi, $step] = match (mb_strtolower($unitShort[$unit] ?? DB::table('units')->where('id', $unit)->value('short_name'))) {
            'g', 'ml' => [50, 500, 50],
            'mg', 'µl' => [100, 2000, 100],
            'kg', 'l' => [1, 5, 1],
            default => [1, 10, 1],
        };
        $min = mt_rand((int) ($lo / $step), (int) ($hi / $step)) * $step;
        return [$min, $min * mt_rand(2, 6)];
    };
    $done = [];
    $onTier = [];
    foreach ($deptRows as $r) {
        $bp = $r['Bophan'];
        if (! isset(CMS3['departments'][$bp])) {
            continue;
        }
        $dept = CMS3['departments'][$bp]['id'];
        $cat = $catOf[$r['Mahoachat']] ?? null;
        $dinhkhu = cms3_clean($r['Dinhkhu']);
        $line = [$bp, $r['Mahoachat'], $cat ? $catCode[$cat] : '', cms3_clean($r['TenhoachatTA']), cms3_clean($r['DVT']), $dinhkhu, '', '', '', '', ''];
        if (cms3_is_test_row($r)) {
            $inc('dept_skip_test_row');
            continue;
        }
        if (! $cat) {
            $line[10] = 'Bỏ qua: mã chưa có trong danh mục chung';
            $report[] = $line;
            $inc('dept_skip_no_category');
            continue;
        }
        $unit = $resolveUnit((string) $r['DVT']);
        if (! $unit) {
            $line[10] = 'Bỏ qua: đơn vị "' . cms3_clean($r['DVT']) . '" chưa có trong Dữ Liệu Gốc';
            $report[] = $line;
            $inc('dept_skip_unit');
            continue;
        }
        if (isset($done[$dept . '|' . $cat])) {
            $line[10] = 'Gộp: phòng đã khai mã danh mục này từ mã Access ' . $done[$dept . '|' . $cat];
            $report[] = $line;
            $inc('dept_merged');
            continue;
        }
        $p = parseLocation($dinhkhu);
        [$locId, $locCode, $tierId] = $p ? $locationFor($bp, $p) : [null, '', null];
        if ($tierId) {
            $onTier[$tierId][] = $cat;
        }
        [$min, $max] = $randStock($unit);
        $months = (int) ($r['SNHD'] ?? 0);
        DB::table('chemical_department_categories')->insert(['department_id' => $dept, 'category_id' => $cat, 'unit_id' => $unit,
            'pack_weight_kg' => null, 'shelf_life_months' => $months > 0 ? $months : null, 'default_location_id' => $locId,
            'storage_condition_id' => null, 'min_stock' => $min, 'max_stock' => $max,
            'note' => mb_substr('CMS 3.0: ' . $r['Mahoachat'] . ($dinhkhu !== '' ? ', định khu cũ ' . $dinhkhu : '')
                . (cms3_nullish($r['Mucdichsudung']) ? ', ' . cms3_clean($r['Mucdichsudung']) : ''), 0, 500),
            'status_id' => 1, 'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now]);
        $done[$dept . '|' . $cat] = $r['Mahoachat'];
        $inc('dept_rows_' . $bp);
        [$line[6], $line[7], $line[8], $line[9]] = [$locCode ?: '(không đặt)', $min, $max, $months ?: ''];
        $report[$bp . '|' . $cat] = $line;
    }

    /* ---------- 5. Tương kỵ cùng phạm vi (mặc định cùng Tầng - config chemical.incompatibility_scope) ---------- */
    $with = [];
    foreach ($onTier as $cats) {
        $cats = array_values(array_unique($cats));
        foreach ($cats as $i => $a) {
            foreach (array_slice($cats, $i + 1) as $b) {
                if (ChemicalCompatibility::pairs($catWarn[$a] ?? [], $catWarn[$b] ?? [])) {
                    $with[$a][] = $catCode[$b];
                    $with[$b][] = $catCode[$a];
                }
            }
        }
    }
    foreach ($report as $k => $line) {
        $cat = is_string($k) ? (int) substr($k, strpos($k, '|') + 1) : 0;
        if (! empty($with[$cat])) {
            $list = array_values(array_unique($with[$cat]));
            $report[$k][10] = 'Tương kỵ cùng tầng với ' . count($list) . ' mã: ' . implode(', ', array_slice($list, 0, 8)) . (count($list) > 8 ? ', …' : '');
            $inc('dept_rows_incompatible_same_tier');
        }
    }

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/2_categories_report.csv", ['Bộ phận', 'Mã Access', 'Mã danh mục', 'Tên Access (TA)', 'ĐVT', 'Dinhkhu Access',
    'Định khu WMS', 'Tồn tối thiểu', 'Tồn tối đa', 'Hạn dùng nội bộ (tháng)', 'Ghi chú'], array_values($report));
$stats['mode'] = $dry ? 'DRY-RUN (rollback)' : 'COMMITTED';
print_r($stats);
