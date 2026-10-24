<?php
/**
 * BƯỚC 4 - BÁO CÁO RÀ SOÁT sau khi nạp (không ghi CSDL).
 *
 * Ra 2 file CSV (UTF-8 BOM, mở thẳng bằng Excel):
 *   4_review_categories.csv : mã danh mục nghi ngờ + mức ưu tiên (1 Cao, 2 Trung bình, 3 Thấp) + vấn đề
 *   4_review_new_ai.csv     : hoạt chất do bộ script tạo (ngoài danh mục luật) để rà tên/CAS/nhóm
 * Muốn file .xlsx có tổng hợp COUNTIF + ô điền kết luận: dùng skill xlsx đọc 2 CSV này.
 *
 * php 4_review.php <thư mục JSON>
 */
require __DIR__ . '/_boot.php';

use App\Support\ChemicalClassification as CC;
use App\Support\ChemicalCompatibility;
use App\Support\DepartmentChemical;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$ACTOR = CMS3['actor'];

$access = [];
foreach (cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")) as $r) {
    $access[$r['Mahoachat']] = $r;
}
$catCodes = [];
foreach (DB::table('chemical_category_histories')->where('action', 'Thêm mới')->get(['chemical_category_id', 'change_note']) as $h) {
    preg_match_all('/\bH\d{4,5}\b/', (string) $h->change_note, $m);
    $catCodes[(int) $h->chemical_category_id] = $m[0];
}

$cats = DB::table('chemical_categories as c')->join('chem_names as cn', 'cn.id', '=', 'c.chem_names_id')
    ->join('manufacturers as m', 'm.id', '=', 'c.manufacturers_id')->orderBy('c.code')
    ->get(['c.id', 'c.code', 'c.type', 'c.status_id', 'c.chem_names_id', 'cn.name as chem', 'm.short_name as nsx']);
$derived = CC::groupsByChemName();
$links = DB::table('chem_name_active_ingredient as p')->join('active_ingredients as a', 'a.id', '=', 'p.active_ingredients_id')
    ->get(['p.chem_names_id', 'p.content_percent', 'a.id', 'a.name', 'a.cas_no', 'a.parent_id', 'a.created_by'])->groupBy('chem_names_id');
$aiGroups = CC::groupsForActiveIngredients($links->flatten()->pluck('id')->unique()->all());
$aiName = DB::table('active_ingredients')->pluck('name', 'id');
$hazard = DB::table('chem_name_mixture_hazard_category')->pluck('chem_names_id')->flip();
$water = DB::table('active_ingredients')->where('cas_no', '7732-18-5')->value('id');

// Tương kỵ cùng phạm vi định khu, theo từng phòng
$conflictCount = [];
foreach (CMS3['departments'] as $bp => $cfg) {
    $rows = DepartmentChemical::rowsOfDepartment($cfg['id']);
    $byRow = $rows->keyBy('id');
    foreach (ChemicalCompatibility::conflictsByDepartmentRows($rows) as $rowId => $c) {
        $cat = (int) $byRow[$rowId]->category_id;
        $conflictCount[$cat][$bp] = count($c['conflicts']);
    }
}
$conc = '/(\d+(?:[.,]\d+)?\s*(%|N\b|M\b|mol|ppm|mg\/l|g\/l))|absolute|tuyệt đối|anhydrous|khan|grade|hplc|for analysis|spectroscopy|lc-ms|gc\b/iu';

$out = [];
foreach ($cats as $c) {
    $codes = $catCodes[$c->id] ?? [];
    $lk = $links[$c->chem_names_id] ?? collect();
    $isMix = $lk->count() >= 2;
    $issues = [];
    $prio = 9;
    $add = function (string $t, int $p) use (&$issues, &$prio) { $issues[] = $t; $prio = min($prio, $p); };

    $acc = [];
    $taNames = [];
    foreach ($codes as $code) {
        if (! isset($access[$code])) {
            continue;
        }
        $acc = array_merge($acc, cms3_access_groups($access[$code]), ! empty($access[$code]['HC_Cam']) ? [11] : []);
        $taNames[] = cms3_clean($access[$code]['TenhoachatTA']);
    }
    $acc = array_values(array_unique($acc));
    sort($acc);
    $der = array_values(array_filter($derived[$c->chem_names_id] ?? [], fn ($g) => $g <= 11));
    if ($acc != $der) {
        $legal = array_diff(array_merge(array_diff($acc, $der), array_diff($der, $acc)), [10]);
        $add('Lệch nhóm: Access [' . implode(',', $acc) . '] ≠ WMS [' . implode(',', $der) . ']', $legal ? 1 : 2);
    }
    if (in_array(10, $acc, true) && ! in_array(10, $der, true)) {
        $add('Access ghi nhóm 10 nhưng WMS không suy ra (hỗn hợp cần thành phần nhóm 9 + tick Bảng B)', 1);
    }
    if (isset($hazard[$c->chem_names_id]) && CMS3['table_b_default_code']) {
        $add('Nhóm 10: Bảng B đang tick MẶC ĐỊNH ' . CMS3['table_b_default_code'] . ' - chọn đúng mục nguy hại', 2);
    }
    foreach ($lk as $p) {
        if ($p->created_by === $ACTOR && $p->parent_id) {
            $add('Hoạt chất tạo mới tự gắn mục gộp "' . $aiName[$p->parent_id] . '": ' . $p->name . ' - kiểm tra mục gộp, hệ số quy đổi', 1);
        } elseif ($p->created_by === $ACTOR && array_intersect($aiGroups[$p->id] ?? [], CC::SINGLE_SUBSTANCE_GROUPS)) {
            $add('Hoạt chất ngoài danh mục luật được gán nhóm ' . implode(',', $aiGroups[$p->id]) . ' theo Access: ' . $p->name, 1);
        }
        if ($isMix && $p->content_percent === null && array_intersect($aiGroups[$p->id] ?? [], [3, 4, 5, 6, 7])) {
            $add('Thành phần nhóm 3-7 chưa có % (chưa xét được nhóm 8): ' . $p->name, 2);
        }
    }
    if ($isMix && $water && $lk->count() === 2 && $lk->pluck('id')->contains($water)) {
        $add('Dung dịch pha loãng: hệ thống tự thêm "Nước" theo phân loại hỗn hợp của Access', 2);
    }
    if (! $isMix && $lk->count() === 1) {
        $lost = array_values(array_unique(array_filter($taNames, fn ($n) => preg_match($conc, $n) && ! preg_match($conc, $c->chem))));
        if ($lost) {
            $add('Đơn chất đặt tên theo hoạt chất - mất nồng độ/cấp độ của tên Access: "' . implode('" / "', $lost) . '"', 2);
        }
        if ($lk->first()->cas_no === null) {
            $add('Đơn chất không có số CAS (chưa đối chiếu được danh mục luật)', 3);
        }
    }
    if (preg_match('/^[A-Z]{1,3}\d{1,3}$|^([A-Z][a-z]?\d*){2,}$/u', $c->chem)) {
        $add('Tên hoá chất dạng mã/công thức: "' . $c->chem . '"', 1);
    }
    if ($c->type !== null && preg_match('/^[A-Z]\d+$/', $c->type)) {
        $add('Loại lạ: "' . $c->type . '"', 2);
    }
    if (preg_match('/\(H\d{4,5}\)$/', $c->chem)) {
        $add('Tên trùng tên hoá chất khác nên bị gắn mã Access', 3);
    }
    if ((int) $c->status_id === 0) {
        $add('Mã đang khoá (Access Active = False)', 3);
    }
    foreach ($conflictCount[$c->id] ?? [] as $bp => $n) {
        $add("Tương kỵ cùng tầng với $n mã ở $bp - xếp lại định khu", 2);
    }
    if ($issues) {
        $out[] = [[1 => 'Cao', 2 => 'Trung bình', 3 => 'Thấp'][$prio], $c->code, implode(', ', $codes), $c->chem, implode(' | ', array_unique($taNames)),
            $c->nsx, $c->type, $isMix ? 'Hỗn hợp' : 'Đơn chất', implode('; ', $lk->pluck('name')->all()), implode(',', $acc), implode(',', $der),
            implode("\n", $issues), '', '', ''];
    }
}
usort($out, fn ($a, $b) => [array_search($a[0], ['Cao', 'Trung bình', 'Thấp']), $a[1]] <=> [array_search($b[0], ['Cao', 'Trung bình', 'Thấp']), $b[1]]);
cms3_write_csv("$dir/4_review_categories.csv", ['Mức ưu tiên', 'Mã danh mục', 'Mã Access', 'Tên hoá chất WMS', 'Tên Access (TA)', 'NSX', 'Loại',
    'Đơn chất/Hỗn hợp', 'Hoạt chất', 'Nhóm Access', 'Nhóm WMS', 'Vấn đề', 'Kết luận', 'Người kiểm tra', 'Ngày kiểm tra'], $out);

$ai = [];
foreach (DB::table('active_ingredients as a')->leftJoin('active_ingredients as p', 'p.id', '=', 'a.parent_id')->where('a.created_by', $ACTOR)
    ->orderBy('a.code')->get(['a.id', 'a.code', 'a.name', 'a.name_en', 'a.cas_no', 'a.chemical_formula', 'p.name as parent']) as $a) {
    $used = DB::table('chem_name_active_ingredient as p')->join('chem_names as c', 'c.id', '=', 'p.chem_names_id')->where('p.active_ingredients_id', $a->id)->pluck('c.name')->implode('; ');
    $ai[] = [$a->code, $a->name, $a->name_en, $a->cas_no, $a->chemical_formula, implode(',', CC::groupsForActiveIngredient($a->id)), $a->parent, $used, ''];
}
cms3_write_csv("$dir/4_review_new_ai.csv", ['Mã', 'Tên hoạt chất', 'Tên tiếng Anh', 'CAS', 'Công thức', 'Nhóm', 'Thuộc mục gộp', 'Dùng trong tên hoá chất', 'Kết luận'], $ai);

$count = array_count_values(array_column($out, 0));
echo count($out), ' mã nghi ngờ ', json_encode($count, JSON_UNESCAPED_UNICODE), '; ', count($ai), " hoạt chất tạo mới\n";
