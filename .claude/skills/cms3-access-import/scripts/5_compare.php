<?php
/**
 * Đối chiếu danh mục hoá chất WMS (mã C) với Access CMS 3.0 (mã H) - mỗi mã H một dòng.
 * php 5_compare.php <thư mục JSON>  (sau bước 4: dùng thêm 4_review_*.csv)   -> <thư mục>/compare.json
 */
require __DIR__ . '/_boot.php';

use App\Support\ChemicalClassification as CC;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1], '/\\');

// Mã H -> id danh mục (lịch sử "Thêm mới" do bước 2 ghi)
$catOfH = [];
foreach (DB::table('chemical_category_histories')->where('action', 'Thêm mới')->get(['chemical_category_id', 'change_note']) as $h) {
    preg_match_all('/\bH\d{4,5}\b/', (string) $h->change_note, $m);
    foreach ($m[0] as $code) {
        $catOfH[$code] = (int) $h->chemical_category_id;
    }
}
$hOfCat = [];
foreach ($catOfH as $h => $c) {
    $hOfCat[$c][] = $h;
}

$cats = DB::table('chemical_categories as c')
    ->join('chem_names as cn', 'cn.id', '=', 'c.chem_names_id')
    ->join('manufacturers as m', 'm.id', '=', 'c.manufacturers_id')
    ->leftJoin('storage_conditions as s', 's.id', '=', 'c.storage_condition_id')
    ->get(['c.id', 'c.code', 'c.type', 'c.status_id', 'c.app_status', 'c.safety_warning', 'c.chem_names_id', 'cn.name as chem',
           'cn.is_banned', 'm.short_name as nsx', 'm.name as nsx_full', 's.name as storage', 'c.density'])->keyBy('id');
$derived = CC::groupsByChemName();
$links = DB::table('chem_name_active_ingredient as p')->join('active_ingredients as a', 'a.id', '=', 'p.active_ingredients_id')
    ->get(['p.chem_names_id', 'p.content_percent', 'a.code', 'a.name', 'a.cas_no'])->groupBy('chem_names_id');
$warnLabel = config('chemical.safety_warnings');
$label = fn (array $codes) => implode(', ', array_map(fn ($k) => is_array($warnLabel[$k] ?? null) ? ($warnLabel[$k]['label'] ?? $k) : ($warnLabel[$k] ?? $k), $codes));

// Danh mục phòng WMS: [dept|cat] -> ĐVT, định khu, hạn dùng
$deptIds = array_map(fn ($c) => $c['id'], CMS3['departments']);
$wmsDept = [];
foreach (DB::table('chemical_department_categories as d')
    ->leftJoin('units as u', 'u.id', '=', 'd.unit_id')
    ->leftJoin('locations as l', 'l.id', '=', 'd.default_location_id')
    ->whereIn('d.department_id', $deptIds)
    ->get(['d.department_id', 'd.category_id', 'u.short_name as unit', 'l.code as loc', 'd.shelf_life_months', 'd.status_id']) as $d) {
    $wmsDept[$d->department_id . '|' . $d->category_id] = $d;
}
// Danh mục phòng Access: [BP|H] -> dòng
$accDept = [];
foreach (cms3_json("$dir/dmhc_dept.json") as $d) {
    $accDept[$d['Bophan'] . '|' . $d['Mahoachat']] = $d;
}

$lc = fn ($s) => mb_strtolower(cms3_clean($s));
$rows = [];
$seenCats = [];
foreach (cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")) as $r) {
    $h = $r['Mahoachat'];
    $catId = $catOfH[$h] ?? null;
    $c = $catId ? ($cats[$catId] ?? null) : null;
    $accGroups = array_merge(cms3_access_groups($r), ! empty($r['HC_Cam']) ? [11] : []);
    sort($accGroups);
    $accWarn = cms3_safety_warnings($r);
    $accCas = cms3_cas_list($r['CASnumber']);
    sort($accCas);

    $row = [
        'h' => $h, 'c' => $c->code ?? '',
        'map' => $c ? 'Đã nạp' : (cms3_is_test_row($r) ? 'Không nạp - dữ liệu thử (OQ..)' : 'Không nạp - chưa có trong WMS'),
        'merged' => $c ? implode(', ', array_diff($hOfCat[$catId] ?? [], [$h])) : '',
        'a_ta' => cms3_clean($r['TenhoachatTA']), 'a_tv' => cms3_clean($r['TenhoachatTV']), 'a_cas' => implode(', ', $accCas),
        'a_nsx' => cms3_clean($r['NSX']), 'a_loai' => cms3_nullish($r['Loai']) ?? '', 'a_dvt' => cms3_clean($r['DVT']),
        'a_active' => ! empty($r['Active']) ? 'Hoạt động' : 'Khoá', 'a_groups' => implode(',', $accGroups),
        'a_warn' => $label($accWarn),
    ];
    $cmp = [];
    if ($c) {
        $seenCats[$catId] = true;
        $lk = $links[$c->chem_names_id] ?? collect();
        $wCas = $lk->pluck('cas_no')->filter()->unique()->sort()->values()->all();
        $wGroups = array_values(array_filter($derived[$c->chem_names_id] ?? [], fn ($g) => $g <= 11));
        sort($wGroups);
        $wWarn = json_decode((string) $c->safety_warning, true) ?: [];
        $row += [
            'w_name' => $c->chem, 'w_type' => $lk->count() >= 2 ? 'Hỗn hợp' : 'Đơn chất',
            'w_ai' => $lk->map(fn ($p) => $p->code . ' ' . $p->name . ($p->cas_no ? ' [' . $p->cas_no . ']' : '') . ($p->content_percent !== null ? ' ' . (float) $p->content_percent . '%' : ''))->implode("\n"),
            'w_cas' => implode(', ', $wCas), 'w_nsx' => $c->nsx, 'w_nsx_full' => $c->nsx_full, 'w_loai' => $c->type ?? '',
            'w_status' => ((int) $c->status_id === 1 ? 'Hoạt động' : 'Khoá') . ($c->app_status === 'approved' ? '' : ' (chờ duyệt)'),
            'w_groups' => implode(',', $wGroups), 'w_warn' => $label($wWarn), 'w_storage' => $c->storage ?? '',
        ];
        // So sánh: Đúng / Khác
        $cmp['Tên'] = $lc($row['a_ta']) === $lc($c->chem) || $lc($row['a_tv']) === $lc($c->chem) ? 'Đúng'
            // Quy tắc đã chốt: đơn chất đặt tên = tên hoạt chất (tiếng Việt) -> không tính là lệch
            : ($lk->count() === 1 && $lc($lk->first()->name) === $lc($c->chem) ? 'Theo tên hoạt chất' : 'Khác');
        // "Nước" do bộ nạp tự thêm cho dung dịch pha loãng -> không tính là lệch
        $casNoWater = array_values(array_diff($wCas, in_array('7732-18-5', $accCas, true) ? [] : ['7732-18-5']));
        $cmp['CAS'] = array_values(array_diff($accCas, $wCas)) === [] && array_values(array_diff($casNoWater, $accCas)) === [] ? 'Đúng' : 'Khác';
        $cmp['NSX'] = $lc($row['a_nsx']) === $lc($c->nsx) ? 'Đúng' : 'Khác';
        $cmp['Loại'] = $lc($row['a_loai']) === $lc($c->type ?? '') ? 'Đúng' : 'Khác';
        $cmp['Trạng thái'] = ($row['a_active'] === 'Hoạt động') === ((int) $c->status_id === 1) ? 'Đúng' : 'Khác';
        $cmp['Nhóm'] = $row['a_groups'] === $row['w_groups'] ? 'Đúng' : 'Khác';
        $aw = $accWarn; sort($aw); $ww = $wWarn; sort($ww);
        $cmp['Cảnh báo'] = $aw === $ww ? 'Đúng' : 'Khác';
    }
    foreach (CMS3['departments'] as $bp => $cfg) {
        $ad = $accDept[$bp . '|' . $h] ?? null;
        $wd = $c ? ($wmsDept[$cfg['id'] . '|' . $catId] ?? null) : null;
        $row["a_{$bp}_dvt"] = $ad ? cms3_clean($ad['DVT']) : '';
        $row["a_{$bp}_dk"] = $ad ? cms3_clean($ad['Dinhkhu']) : '';
        $row["a_{$bp}_snhd"] = $ad && (int) $ad['SNHD'] ? (int) $ad['SNHD'] : '';
        $row["w_{$bp}_dvt"] = $wd->unit ?? '';
        $row["w_{$bp}_dk"] = $wd->loc ?? '';
        $row["w_{$bp}_snhd"] = $wd && $wd->shelf_life_months ? (int) $wd->shelf_life_months : '';
        if ($ad && ! cms3_is_test_row($ad) && $c) {
            $cmp["ĐVT $bp"] = ! $wd ? 'Thiếu ở WMS' : ($lc($ad['DVT']) === $lc($wd->unit) ? 'Đúng' : 'Khác');
            $cmp["Hạn dùng $bp"] = ! $wd ? 'Thiếu ở WMS' : ((int) $ad['SNHD'] === (int) $wd->shelf_life_months ? 'Đúng' : 'Khác');
        }
    }
    $row['cmp'] = $cmp;
    $row['n_diff'] = count(array_filter($cmp, fn ($v) => ! in_array($v, ['Đúng', 'Theo tên hoạt chất'], true)));
    $rows[] = $row;
}
// Danh mục WMS không có mã H nào (tạo tay trên WMS)
foreach ($cats as $id => $c) {
    if (! isset($seenCats[$id])) {
        $rows[] = ['h' => '', 'c' => $c->code, 'map' => 'Chỉ có ở WMS (không có mã Access)', 'merged' => '', 'w_name' => $c->chem,
            'w_nsx' => $c->nsx, 'w_loai' => $c->type ?? '', 'cmp' => [], 'n_diff' => 0];
    }
}
file_put_contents("$dir/compare.json", json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$stat = [];
foreach ($rows as $r) {
    foreach ($r['cmp'] as $k => $v) {
        $stat[$k][$v] = ($stat[$k][$v] ?? 0) + 1;
    }
    $stat['map'][$r['map']] = ($stat['map'][$r['map']] ?? 0) + 1;
}
echo json_encode($stat, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n", count($rows), " dòng\n";
