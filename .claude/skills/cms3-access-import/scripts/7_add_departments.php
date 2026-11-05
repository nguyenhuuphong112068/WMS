<?php
/**
 * BƯỚC 7 - THÊM DANH MỤC HOÁ CHẤT CỦA PHÒNG cho bộ phận Access mới, KHÔNG đụng danh mục công ty / QC1 / QC2.
 * (Bước 2 xoá & dựng lại cả danh mục công ty nên không chạy lại được khi đã có dữ liệu thật.)
 *
 *   - Nguồn: dmhc_dept.json xuất bằng export_access.ps1 -Departments QC,AD (= Q_DMHC_QC3HH / Q_DMHC_QC4HH).
 *   - Mã Access -> mã danh mục công ty: lịch sử "Thêm mới" (cms3_category_of_access_code, đã tính mã tách ở bước 6).
 *     Mã chưa có danh mục -> bỏ qua, ghi báo cáo.
 *   - Định khu dựng theo Dinhkhu như bước 2 (cms3_parse_location_ext: thêm dạng T1-A1, TL1-10, TLA1...).
 *   - Quy tắc dòng khai giống bước 2: ĐVT = DVT, shelf_life_months = SNHD (tháng), min/max ngẫu nhiên (seed cố định),
 *     nhiều mã Access cùng 1 mã danh mục -> 1 dòng.
 *
 * CHẠY LẠI = xoá dòng khai phòng + định khu do bộ script tạo của các phòng đó rồi dựng lại; dừng nếu phòng đã có phiếu nhập.
 * php 7_add_departments.php <thư mục JSON> QC,AD [--dry]          Ra: <thư mục>/7_add_departments_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\ChemicalCompatibility;
use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$bps = array_values(array_filter(explode(',', (string) ($argv[2] ?? ''))));
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();
mt_srand(CMS3['random_seed']);

if (! $bps || array_diff($bps, array_keys(CMS3['departments']))) {
    throw new RuntimeException('Khai bộ phận trong CMS3[departments] (_boot.php) trước: ' . implode(',', $bps));
}
$deptIds = array_map(fn ($bp) => CMS3['departments'][$bp]['id'], $bps);

$pad = fn ($v) => ctype_digit((string) $v) ? str_pad((string) (int) $v, 2, '0', STR_PAD_LEFT) : (string) $v;
$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu bộ script tạo trước đó cho các phòng này ---------- */
    if (DB::table('chemical_imports')->whereIn('department_id', $deptIds)->exists()
        || DB::table('chemical_department_categories')->whereIn('department_id', $deptIds)->where('created_by', '<>', $ACTOR)->exists()) {
        throw new RuntimeException('Phòng đã có phiếu nhập hoặc dòng khai do người dùng tạo - không xoá để dựng lại.');
    }
    DB::table('chemical_department_categories')->whereIn('department_id', $deptIds)->delete();
    foreach (['locations', 'tiers', 'columns', 'shelves'] as $t) {
        DB::table($t)->whereIn('department_id', $deptIds)->where('created_by', $ACTOR)->delete();
    }
    $whCodes = array_map(fn ($bp) => CMS3['departments'][$bp]['warehouse_code'], $bps);
    DB::table('warehouses')->whereIn('code', $whCodes)->where('created_by', $ACTOR)->delete();

    $catOf = cms3_category_of_access_code();
    $cats = DB::table('chemical_categories')->get(['id', 'code', 'safety_warning'])->keyBy('id');

    /* ---------- 1. Đơn vị ---------- */
    $unitId = [];
    foreach (DB::table('units')->get(['id', 'short_name']) as $u) {
        $unitId[mb_strtolower($u->short_name)] = (int) $u->id;
    }
    $resolveUnit = function (string $dvt) use (&$unitId) {
        $k = mb_strtolower(cms3_clean($dvt));
        $k = CMS3['unit_aliases'][$k] ?? $k;
        return $unitId[$k] ?? null;
    };

    /* ---------- 2. Định khu theo Dinhkhu ---------- */
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
                'warehouse_id' => $wh, 'shelf_id' => $ids['shelf'][$sCode], 'column_id' => $ids['col'][$sCode], 'position' => cms3_tier_position($tier)]);
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

    /* ---------- 3. Danh mục hoá chất của phòng ---------- */
    $unitShort = DB::table('units')->pluck('short_name', 'id')->all();
    $randStock = function (int $unit) use ($unitShort) {
        [$lo, $hi, $step] = match (mb_strtolower($unitShort[$unit] ?? '')) {
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
    $rows = cms3_json("$dir/dmhc_dept.json");
    usort($rows, fn ($a, $b) => [$a['Bophan'], $a['Mahoachat']] <=> [$b['Bophan'], $b['Mahoachat']]);
    foreach ($rows as $r) {
        $bp = $r['Bophan'];
        if (! in_array($bp, $bps, true)) {
            continue;
        }
        $dept = CMS3['departments'][$bp]['id'];
        $cat = $catOf[$r['Mahoachat']] ?? null;
        $cat = $cat && isset($cats[$cat]) ? $cat : null;
        $dinhkhu = cms3_clean($r['Dinhkhu']);
        $line = [$bp, $r['Mahoachat'], $cat ? $cats[$cat]->code : '', cms3_clean($r['TenhoachatTA']), cms3_clean($r['DVT']), $dinhkhu, '', '', '', '', ''];
        if (cms3_is_test_row($r)) {
            $inc('skip_test_row');
            continue;
        }
        if (! $cat) {
            $line[10] = 'Bỏ qua: mã chưa có trong danh mục công ty';
            $report[] = $line;
            $inc('skip_no_category');
            continue;
        }
        $unit = $resolveUnit((string) $r['DVT']);
        if (! $unit) {
            $line[10] = 'Bỏ qua: đơn vị "' . cms3_clean($r['DVT']) . '" chưa có trong Dữ Liệu Gốc';
            $report[] = $line;
            $inc('skip_unit');
            continue;
        }
        if (isset($done[$dept . '|' . $cat])) {
            $line[10] = 'Gộp: phòng đã khai mã danh mục này từ mã Access ' . $done[$dept . '|' . $cat];
            $report[] = $line;
            $inc('merged');
            continue;
        }
        $p = cms3_parse_location_ext($dinhkhu);
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
        $inc('rows_' . $bp);
        [$line[6], $line[7], $line[8], $line[9]] = [$locCode ?: '(không đặt)', $min, $max, $months ?: ''];
        $report[$bp . '|' . $cat] = $line;
    }

    /* ---------- 4. Tương kỵ cùng tầng (chỉ báo cáo, giữ đúng định khu Access) ---------- */
    $warn = fn ($cat) => json_decode((string) ($cats[$cat]->safety_warning ?? ''), true) ?: [];
    $with = [];
    foreach ($onTier as $list) {
        $list = array_values(array_unique($list));
        foreach ($list as $i => $a) {
            foreach (array_slice($list, $i + 1) as $b) {
                if (ChemicalCompatibility::pairs($warn($a), $warn($b))) {
                    $with[$a][] = $cats[$b]->code;
                    $with[$b][] = $cats[$a]->code;
                }
            }
        }
    }
    foreach ($report as $k => $line) {
        $cat = is_string($k) ? (int) substr($k, strpos($k, '|') + 1) : 0;
        if (! empty($with[$cat])) {
            $list = array_values(array_unique($with[$cat]));
            $report[$k][10] = 'Tương kỵ cùng tầng với ' . count($list) . ' mã: ' . implode(', ', array_slice($list, 0, 8)) . (count($list) > 8 ? ', …' : '');
            $inc('incompatible_same_tier');
        }
    }

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/7_add_departments_report.csv", ['Bộ phận', 'Mã Access', 'Mã danh mục', 'Tên Access (TA)', 'ĐVT', 'Dinhkhu Access',
    'Định khu WMS', 'Tồn tối thiểu', 'Tồn tối đa', 'Hạn dùng nội bộ (tháng)', 'Ghi chú'], array_values($report));
$stats['mode'] = $dry ? 'DRY-RUN (rollback)' : 'COMMITTED';
print_r($stats);
