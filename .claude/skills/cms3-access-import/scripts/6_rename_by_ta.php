<?php
/**
 * BƯỚC 6 (chuyển đổi TẠI CHỖ, 04/11/2026) - TÊN HOÁ CHẤT = TenhoachatTA CHO MỌI MÃ ACCESS.
 *
 * Bước 1 cũ đặt tên đơn chất = tên hoạt chất (gộp nhiều mã Access cùng hoạt chất). Người dùng đổi quy tắc:
 *   - Tên hoá chất = Dbo_T_DMHC_Chung.TenhoachatTA (khoá so trùng bỏ hoa/thường + mọi dấu cách, gạch...).
 *   - Thành phần vẫn là hoạt chất ở Dữ Liệu Gốc đơn chất (giữ liên kết cũ của mã Access đó).
 *   - Cùng TenhoachatTA mà thành phần khác => GỘP 1 tên, thành phần = bộ nhiều thành phần nhất.
 *   - Mã danh mục công ty đã gộp nhiều mã Access khác TenhoachatTA => TÁCH: nhóm chứa mã H nhỏ nhất giữ mã C cũ,
 *     nhóm khác sinh mã C mới; dòng khai phòng + lô tồn đầu kỳ (Import CMS 3.0) đi theo mã H của chúng.
 *     Mã H tách ra mà hoạt chất cũ không khớp CAS Access -> tìm hoạt chất theo CAS -> theo tên -> tạo mới
 *     (khi Access không có CAS và hoạt chất cũ cũng không có CAS số, vd chủng vi sinh).
 *
 * Làm tại chỗ (không xoá/nạp lại) vì đã có phiếu xuất + phiếu nhập do người dùng tạo. Id danh mục cũ giữ nguyên.
 * php 6_rename_by_ta.php <thư mục JSON> [--dry]     Ra: <thư mục>/6_rename_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\AccessLegacyCode;
use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();

$tkey = fn ($s) => preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(cms3_clean($s)));
$casOf = fn ($s) => array_values(array_unique(array_filter(array_map('trim', preg_split('/[,;\r\n]+/', (string) $s)),
    fn ($t) => (bool) preg_match('/^\d{2,7}-\d{2}-\d$/', $t))));

$R = [];
foreach (cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")) as $r) {
    $R[$r['Mahoachat']] = $r;
}
$deptOf = [];
foreach (cms3_json("$dir/dmhc_dept.json") as $r) {
    $deptOf[$r['Bophan'] . '|' . $r['Mahoachat']] = $r;
}
$lotH = [];
foreach (cms3_json("$dir/lots.json") as $l) {
    $lotH[$l['Dept'] . '|' . $l['Maxn']] = $l['Mahoachat'];
}
$bpOfDept = [];
foreach (CMS3['departments'] as $bp => $cfg) {
    $bpOfDept[$cfg['id']] = $bp;
}

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];   // H => dòng báo cáo

DB::beginTransaction();
try {
    /* ---------- 0. Hiện trạng: mã H -> danh mục -> tên hoá chất ---------- */
    $catCodes = AccessLegacyCode::byChemicalCategory();
    $cats = DB::table('chemical_categories')->get()->keyBy('id');
    $chemName = DB::table('chem_names')->pluck('name', 'id')->all();
    $catOfH = [];
    foreach ($catCodes as $catId => $hs) {
        foreach ($hs as $h) {
            if (! isset($R[$h])) {
                throw new RuntimeException("Mã $h (danh mục $catId) không có trong dmhc_chung.json");
            }
            $catOfH[$h] = $catId;
        }
    }
    ksort($catOfH);
    $oldChem = array_map(fn ($c) => (int) $cats[$c]->chem_names_id, $catOfH);
    $keyOf = array_map(fn ($h) => $tkey($R[$h]['TenhoachatTA']), array_combine(array_keys($catOfH), array_keys($catOfH)));
    $byKey = [];
    foreach ($keyOf as $h => $k) {
        $byKey[$k][] = $h;
    }
    $nameOfKey = array_map(fn ($hs) => mb_substr(cms3_clean($R[$hs[0]]['TenhoachatTA']), 0, 255), $byKey);

    $pivot = [];
    foreach (DB::table('chem_name_active_ingredient')->orderBy('id')->get() as $p) {
        $pivot[$p->chem_names_id][$p->active_ingredients_id] = $p->content_percent;
    }
    $hazard = [];
    foreach (DB::table('chem_name_mixture_hazard_category')->get() as $p) {
        $hazard[$p->chem_names_id][$p->mixture_hazard_categories_id] = 1;
    }
    $chemRow = DB::table('chem_names')->get()->keyBy('id');
    $ai = DB::table('active_ingredients')->get()->keyBy('id');
    $aiByCas = [];
    $aiByName = [];
    foreach ($ai as $a) {
        if (! $a->is_collective && preg_match('/^\d{2,7}-\d{2}-\d$/', (string) $a->cas_no)) {
            $aiByCas[$a->cas_no][] = $a->id;
        }
        foreach ([$a->name, $a->name_en] as $n) {
            if (($k = $tkey($n)) !== '') {
                $aiByName[$k] ??= $a->id;
            }
        }
    }

    /* ---------- 1. Nhóm theo TenhoachatTA trong từng danh mục ---------- */
    $groupsOfCat = [];   // catId => [key => [H...]] theo thứ tự mã H nhỏ nhất
    foreach ($catOfH as $h => $catId) {
        $groupsOfCat[$catId][$keyOf[$h]][] = $h;
    }
    $splitH = [];   // mọi mã H của danh mục bị tách (kể cả nhóm giữ mã cũ) - phải kiểm lại hoạt chất
    foreach ($groupsOfCat as $catId => $groups) {
        if (count($groups) > 1) {
            $inc('categories_split');
            foreach ($groups as $hs) {
                foreach ($hs as $h) {
                    $splitH[$h] = true;
                }
            }
        }
    }

    /* ---------- 2. Thành phần từng mã H ---------- */
    $nextAiNo = (int) substr((string) DB::table('active_ingredients')->where('code', 'like', 'A%')->max('code'), 1);
    $comps = [];
    $aiNote = [];
    foreach ($catOfH as $h => $catId) {
        $inherited = $pivot[$oldChem[$h]] ?? [];
        $comps[$h] = $inherited;
        $aiNote[$h] = '';
        if (empty($splitH[$h]) || count($inherited) !== 1) {
            continue;
        }
        $r = $R[$h];
        $old = $ai[array_key_first($inherited)];
        $cas = $casOf($r['CASnumber']);
        $oldHasCas = (bool) preg_match('/^\d{2,7}-\d{2}-\d$/', (string) $old->cas_no);
        if ($cas && in_array($old->cas_no, $cas, true)) {
            $aiNote[$h] = 'Giữ hoạt chất cũ (khớp CAS)';
            continue;
        }
        $found = null;
        if (count($cas) === 1 && count($aiByCas[$cas[0]] ?? []) === 1) {
            [$found, $how] = [$aiByCas[$cas[0]][0], 'Theo CAS ' . $cas[0]];
        }
        if (! $found) {
            foreach ([$r['TenhoachatTV'], $r['TenhoachatTA']] as $n) {
                if (($id = $aiByName[$tkey($n)] ?? null) !== null) {
                    [$found, $how] = [$id, 'Theo tên'];
                    break;
                }
            }
        }
        if (! $found && ! $cas && ! $oldHasCas) {
            $name = mb_substr(cms3_clean($r['TenhoachatTV']) ?: cms3_clean($r['TenhoachatTA']), 0, 255);
            $found = DB::table('active_ingredients')->insertGetId(['parent_id' => null,
                'code' => 'A' . str_pad((string) ++$nextAiNo, 5, '0', STR_PAD_LEFT), 'name' => $name,
                'name_en' => mb_substr(cms3_clean($r['TenhoachatTA']), 0, 255), 'cas_no' => $old->cas_no,
                'chemical_formula' => $old->chemical_formula === $old->cas_no ? $old->chemical_formula : null,
                'threshold_kg' => null, 'equiv_factor' => 1, 'is_collective' => 0, 'legal_ref' => $old->legal_ref, 'is_statutory' => 0,
                'note' => null, 'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR,
                'approved_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DataMasterHistory::record('active_ingredients', $found, 'Thêm mới',
                'Nạp từ CMS 3.0 (tách theo TenhoachatTA, mã ' . $h . '): ' . $name . '. Trước đó gắn nhầm hoạt chất ' . $old->name . '.',
                ['name' => 'Tên hoạt chất', 'name_en' => 'Tên tiếng Anh', 'cas_no' => 'Số CAS', 'chemical_formula' => 'Công thức hoá học']);
            $ai[$found] = DB::table('active_ingredients')->find($found);
            $aiByName[$tkey($name)] = $found;
            $aiByName[$tkey($r['TenhoachatTA'])] ??= $found;
            $how = 'Tạo hoạt chất mới';
            $inc('active_ingredients_new');
        }
        if ($found === $old->id) {
            $aiNote[$h] = 'Giữ hoạt chất cũ (khớp tên)';
        } elseif ($found) {
            $comps[$h] = [$found => null];
            $aiNote[$h] = $how . ' (thay ' . $old->name . ')';
            $inc('split_ai_changed');
        } else {
            $aiNote[$h] = 'CẦN RÀ: giữ hoạt chất cũ ' . $old->name . ' [' . $old->cas_no . '], CAS Access ' . (implode(', ', $cas) ?: 'trống');
            $inc('split_ai_review');
        }
    }

    /* ---------- 3. Mỗi khoá TenhoachatTA -> 1 bản ghi chem_names ---------- */
    $want = [];   // key => [components, banned, special, hazards, sources]
    foreach ($byKey as $k => $hs) {
        // Cùng tên mà thành phần khác: lấy bộ NHIỀU thành phần nhất (bằng nhau thì của mã H nhỏ nhất) - hợp
        // lại thì lẫn "hoạt chất" mang tên sản phẩm (Đệm pH 2.00, XLD Agar...) vào hỗn hợp đã khai đủ thành phần
        $c = [];
        $src = [];
        foreach ($hs as $h) {
            if (count($comps[$h]) > count($c)) {
                $c = $comps[$h];
            }
            $src[$oldChem[$h]] = true;
        }
        $src = array_keys($src);
        $hz = [];
        foreach ($src as $x) {
            $hz += $hazard[$x] ?? [];
        }
        $want[$k] = ['components' => $c, 'sources' => $src, 'hazards' => array_keys($hz),
            'banned' => (int) array_reduce($src, fn ($v, $x) => $v || $chemRow[$x]->is_banned, false),
            'special' => (int) array_reduce($src, fn ($v, $x) => $v || $chemRow[$x]->is_special_goods, false)];
        if (count($src) > 1) {
            $inc('names_merged_from_several');
        }
    }

    // Tên cũ giữ id cho khoá của mã H nhỏ nhất nó đang mang; khoá chưa có chủ -> tạo mới
    $chemOfKey = [];
    $firstH = [];
    foreach ($oldChem as $h => $x) {
        $firstH[$x] ??= $h;
    }
    foreach ($firstH as $x => $h) {
        $chemOfKey[$keyOf[$h]] ??= $x;
    }
    $reused = array_flip($chemOfKey);
    $orphans = array_values(array_diff(array_keys($firstH), array_keys($reused)));

    // Đổi tạm mọi tên dính tới để không vướng khoá UNIQUE(name) khi đặt lại
    foreach (array_keys($firstH) as $x) {
        DB::table('chem_names')->where('id', $x)->update(['name' => '__cms3_tmp_' . $x]);
    }
    $finalName = function (string $name, string $h, ?int $self) {
        if (DB::table('chem_names')->where('name', $name)->when($self, fn ($q) => $q->where('id', '<>', $self))->exists()) {
            $name = mb_substr($name, 0, 240) . ' (' . $h . ')';
        }
        return $name;
    };
    $labels = fn ($c) => implode('; ', array_map(fn ($a, $p) => $ai[$a]->name . ($p !== null ? ' (' . rtrim(rtrim(number_format((float) $p, 4, '.', ''), '0'), '.') . '%)' : ''),
        array_keys($c), $c));
    $fields = ['name' => 'Tên hoá chất', 'is_banned' => 'Hoá chất cấm (Luật Đầu tư 2025)', 'is_special_goods' => 'Hàng hoá đặc biệt'];
    $maps = ['is_banned' => [0 => 'Không', 1 => 'Có'], 'is_special_goods' => [0 => 'Không', 1 => 'Có']];

    $syncLinks = function (int $id, array $w) use ($now, &$pivot, &$hazard) {
        $changed = ($pivot[$id] ?? []) != $w['components'];
        if ($changed) {
            DB::table('chem_name_active_ingredient')->where('chem_names_id', $id)->delete();
            foreach ($w['components'] as $aiId => $pct) {
                DB::table('chem_name_active_ingredient')->insert(['chem_names_id' => $id, 'active_ingredients_id' => $aiId,
                    'content_percent' => count($w['components']) > 1 ? $pct : null, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        if (array_keys($hazard[$id] ?? []) != $w['hazards']) {
            DB::table('chem_name_mixture_hazard_category')->where('chem_names_id', $id)->delete();
            foreach ($w['hazards'] as $hz) {
                DB::table('chem_name_mixture_hazard_category')->insert(['chem_names_id' => $id, 'mixture_hazard_categories_id' => $hz,
                    'created_at' => $now, 'updated_at' => $now]);
            }
        }
        return $changed;
    };

    foreach ($byKey as $k => $hs) {
        $w = $want[$k];
        if (isset($chemOfKey[$k])) {
            $id = $chemOfKey[$k];
            $name = $finalName($nameOfKey[$k], $hs[0], $id);
            DB::table('chem_names')->where('id', $id)->update(['name' => $name, 'is_banned' => $w['banned'],
                'is_special_goods' => $w['special'], 'updated_by' => $ACTOR, 'updated_at' => $now]);
            $linksChanged = $syncLinks($id, $w);
            if ($name !== $chemName[$id] || $linksChanged) {
                DataMasterHistory::record('chem_names', $id, 'Cập nhật', 'Đổi tên theo TenhoachatTA CMS 3.0 (mã ' . implode(', ', $hs) . '): '
                    . $chemName[$id] . ' -> ' . $name . '.' . ($linksChanged ? ' Thành phần: ' . $labels($w['components']) . '.' : ''), $fields, $maps);
                $inc('chem_names_renamed');
            }
        } else {
            $src = $chemRow[$w['sources'][0]];
            $name = $finalName($nameOfKey[$k], $hs[0], null);
            $id = DB::table('chem_names')->insertGetId(['name' => $name, 'is_banned' => $w['banned'], 'is_special_goods' => $w['special'],
                'app_status' => $src->app_status, 'status_id' => $src->status_id, 'created_by' => $ACTOR,
                'approved_by' => $src->approved_by ? $ACTOR : null, 'approved_at' => $src->approved_by ? $now : null,
                'created_at' => $now, 'updated_at' => $now]);
            $syncLinks($id, $w);
            DataMasterHistory::record('chem_names', $id, 'Thêm mới', 'Nạp từ CMS 3.0 (mã ' . implode(', ', $hs) . '): ' . $name
                . '. Tách theo TenhoachatTA từ "' . $chemName[$w['sources'][0]] . '" - hoạt chất: ' . $labels($w['components']) . '.', $fields, $maps);
            $chemOfKey[$k] = $id;
            $inc('chem_names_new');
        }
    }

    /* ---------- 4. Danh mục công ty: đổi tên + tách mã ---------- */
    $catHistory = function (int $id, string $action, string $note, ?string $reason = null) use ($ACTOR, $now) {
        $row = DB::table('chemical_categories')->where('id', $id)->first();
        DB::table('chemical_category_histories')->insert(['chemical_category_id' => $row->id, 'action' => $action, 'code' => $row->code,
            'type' => $row->type, 'chem_names_id' => $row->chem_names_id, 'manufacturers_id' => $row->manufacturers_id,
            'density' => $row->density, 'ai_content_percent' => $row->ai_content_percent, 'shelf_life_months' => $row->shelf_life_months,
            'storage_condition_id' => $row->storage_condition_id, 'doc_no' => $row->doc_no, 'classification' => null,
            'safety_warning' => $row->safety_warning, 'lead_time_days' => $row->lead_time_days,
            'national_portal_declared' => $row->national_portal_declared, 'app_status' => $row->app_status, 'status_id' => $row->status_id,
            'change_note' => $note, 'change_reason' => $reason, 'created_by' => $ACTOR, 'created_at' => $now]);
    };
    $nextCatNo = (int) substr((string) DB::table('chemical_categories')->max('code'), 1);
    $newCatOfH = [];
    $finalChemName = DB::table('chem_names')->pluck('name', 'id')->all();
    foreach ($groupsOfCat as $catId => $groups) {
        $cat = $cats[$catId];
        $first = array_key_first($groups);
        foreach ($groups as $k => $hs) {
            if ($k === $first) {
                foreach ($hs as $h) {
                    $newCatOfH[$h] = $catId;
                }
                if ((int) $cat->chem_names_id !== $chemOfKey[$k]) {
                    DB::table('chemical_categories')->where('id', $catId)->update(['chem_names_id' => $chemOfKey[$k],
                        'updated_by' => $ACTOR, 'updated_at' => $now]);
                    $catHistory($catId, 'Cập nhật', 'Tên hoá chất: ' . $chemName[$cat->chem_names_id] . ' -> '
                        . $finalChemName[$chemOfKey[$k]] . ' (đổi theo TenhoachatTA CMS 3.0).');
                    $inc('categories_renamed');
                }
                continue;
            }
            $code = 'C' . str_pad((string) ++$nextCatNo, 5, '0', STR_PAD_LEFT);
            $newId = DB::table('chemical_categories')->insertGetId(['code' => $code, 'type' => $cat->type, 'chem_names_id' => $chemOfKey[$k],
                'manufacturers_id' => $cat->manufacturers_id, 'density' => $cat->density, 'ai_content_percent' => $cat->ai_content_percent,
                'shelf_life_months' => $cat->shelf_life_months, 'storage_condition_id' => $cat->storage_condition_id, 'doc_no' => $cat->doc_no,
                'safety_warning' => $cat->safety_warning, 'app_status' => $cat->app_status, 'status_id' => $cat->status_id,
                'created_by' => $ACTOR, 'approved_by' => $cat->approved_by ? $ACTOR : null, 'approved_at' => $cat->approved_by ? $now : null,
                'created_at' => $now, 'updated_at' => $now, 'lead_time_days' => $cat->lead_time_days, 'lead_time_pending' => $cat->lead_time_pending,
                'national_portal_declared' => 0]);
            $catHistory($newId, 'Thêm mới', 'Nạp từ CMS 3.0, mã cũ: ' . implode(', ', $hs) . '.',
                'Tách từ ' . $cat->code . ' theo TenhoachatTA (tên hoá chất khác nhau).');
            $catHistory($catId, 'Cập nhật', 'Tách mã CMS 3.0 sang ' . $code . ': ' . implode(', ', $hs) . '.',
                'Tên hoá chất theo TenhoachatTA khác nhau.');
            foreach ($hs as $h) {
                $newCatOfH[$h] = $newId;
            }
            $inc('categories_new');
        }
    }

    /* ---------- 5. Dòng khai phòng + lô tồn đầu kỳ đi theo mã H ---------- */
    $unitId = [];
    foreach (DB::table('units')->get(['id', 'short_name']) as $u) {
        $unitId[mb_strtolower($u->short_name)] = (int) $u->id;
    }
    $splitCats = array_keys(array_filter($groupsOfCat, fn ($g) => count($g) > 1));
    $deptRowOf = function (int $dept, int $cat) {
        return DB::table('chemical_department_categories')->where('department_id', $dept)->where('category_id', $cat)->first();
    };
    $ensureDeptRow = function (int $dept, int $cat, string $h, $template, string $fromCode) use ($deptRowOf, $deptOf, $bpOfDept, $unitId, $ACTOR, $now, $inc) {
        if ($deptRowOf($dept, $cat)) {
            return;
        }
        $a = $deptOf[($bpOfDept[$dept] ?? '') . '|' . $h] ?? null;
        $unit = $a ? ($unitId[mb_strtolower(cms3_clean($a['DVT']))] ?? $template->unit_id) : $template->unit_id;
        $months = $a ? (int) ($a['SNHD'] ?? 0) : 0;
        $dinhkhu = $a ? cms3_clean($a['Dinhkhu']) : '';
        DB::table('chemical_department_categories')->insert(['department_id' => $dept, 'category_id' => $cat, 'unit_id' => $unit,
            'pack_weight_kg' => $template->pack_weight_kg, 'shelf_life_months' => $months > 0 ? $months : $template->shelf_life_months,
            'default_location_id' => $template->default_location_id, 'storage_condition_id' => $template->storage_condition_id,
            'min_stock' => $template->min_stock, 'max_stock' => $template->max_stock,
            'note' => mb_substr('CMS 3.0: ' . $h . ($dinhkhu !== '' ? ', định khu cũ ' . $dinhkhu : '') . ' (tách từ ' . $fromCode . ')', 0, 500),
            'status_id' => $template->status_id, 'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now]);
        $inc('dept_rows_new');
    };
    foreach ($splitCats as $catId) {
        $code = $cats[$catId]->code;
        $hsAll = array_merge(...array_values($groupsOfCat[$catId]));
        $templates = [];
        foreach (DB::table('chemical_department_categories')->where('category_id', $catId)->get() as $d) {
            $templates[$d->department_id] = $d;
            $h = preg_match('/CMS 3\.0: (H\d{4,5})/', (string) $d->note, $m) ? $m[1] : null;
            $target = $h ? ($newCatOfH[$h] ?? $catId) : $catId;
            if ($target !== $catId && ! $deptRowOf($d->department_id, $target)) {
                DB::table('chemical_department_categories')->where('id', $d->id)->update(['category_id' => $target, 'updated_by' => $ACTOR, 'updated_at' => $now]);
                $inc('dept_rows_moved');
            }
        }
        // Mã H nào phòng có khai trong Access (trước bị "Gộp") thì phòng có dòng riêng cho mã danh mục của nó
        foreach ($templates as $dept => $tpl) {
            foreach ($hsAll as $h) {
                if (isset($deptOf[($bpOfDept[$dept] ?? '') . '|' . $h])) {
                    $ensureDeptRow($dept, $newCatOfH[$h], $h, $tpl, $code);
                }
            }
        }
        foreach (DB::table('chemical_imports')->where('category_id', $catId)->where('created_by', $ACTOR)->get() as $lot) {
            $maxn = preg_match('/mã XN (\S+?),/', (string) $lot->note, $m) ? $m[1] : null;
            $h = $maxn ? ($lotH[($bpOfDept[$lot->department_id] ?? '') . '|' . $maxn] ?? null) : null;
            if (! $h) {
                $inc('lots_unmapped');
                continue;
            }
            $target = $newCatOfH[$h] ?? $catId;
            if ($target === $catId) {
                continue;
            }
            $ensureDeptRow((int) $lot->department_id, $target, $h, $templates[$lot->department_id] ?? $deptRowOf((int) $lot->department_id, $catId), $code);
            DB::table('chemical_imports')->where('id', $lot->id)->update(['category_id' => $target, 'updated_by' => $ACTOR, 'updated_at' => $now]);
            $row = (array) DB::table('chemical_imports')->where('id', $lot->id)->first();
            DB::table('chemical_import_histories')->insert(array_intersect_key($row, array_flip(['code', 'category_id', 'amount', 'imported_date',
                'imported_by', 'invoice_number', 'invoice_date', 'expired_date', 'internal_expired_date', 'is_microbiological_chemicals', 'batch_no',
                'supplier_id', 'location_id', 'note', 'status_id', 'is_checked', 'check_result', 'checked_by', 'checked_at', 'check_note']))
                + ['import_id' => $lot->id, 'action' => 'Cập nhật', 'reason' => 'Tách mã danh mục theo TenhoachatTA',
                   'change_note' => 'Mã danh mục: ' . $code . ' -> ' . DB::table('chemical_categories')->where('id', $target)->value('code')
                       . ' (mã Access ' . $h . ').', 'created_by' => $ACTOR, 'created_at' => $now]);
            $inc('lots_moved');
        }
        $n = DB::table('chemical_imports')->where('category_id', $catId)->where('created_by', '<>', $ACTOR)->count();
        $p = DB::table('chemical_product_list_items')->where('category_id', $catId)->count();
        if ($n || $p) {
            $stats['kept_on_original'][] = $code . ($n ? " ($n phiếu nhập người dùng)" : '') . ($p ? " ($p dòng DS theo sản phẩm)" : '');
        }
    }

    /* ---------- 6. Tên cũ không còn mã nào dùng -> xoá ---------- */
    foreach ($orphans as $x) {
        if (DB::table('chemical_categories')->where('chem_names_id', $x)->exists()) {
            throw new RuntimeException("chem_names $x còn danh mục trỏ tới");
        }
        $into = $finalChemName[$chemOfKey[$keyOf[$firstH[$x]]]];
        DB::table('chem_name_active_ingredient')->where('chem_names_id', $x)->delete();
        DB::table('chem_name_mixture_hazard_category')->where('chem_names_id', $x)->delete();
        DB::table('chem_names')->where('id', $x)->delete();
        DataMasterHistory::write('chem_names', $x, 'Xoá', 'Gộp vào "' . $into . '" (cùng TenhoachatTA CMS 3.0, mã ' . $firstH[$x] . '). Tên cũ: ' . $chemName[$x] . '.',
            ['Tên hoá chất' => $chemName[$x]]);
        $inc('chem_names_deleted');
    }

    /* ---------- 7. Kiểm tra + báo cáo ---------- */
    $orphan = DB::table('chem_names')->whereNotIn('id', DB::table('chem_name_active_ingredient')->select('chem_names_id'))->count();
    $tmp = DB::table('chem_names')->where('name', 'like', '\_\_cms3\_tmp\_%')->count();
    if ($orphan || $tmp) {
        throw new RuntimeException("Còn $orphan tên không có hoạt chất, $tmp tên tạm");
    }
    $codeOf = DB::table('chemical_categories')->pluck('code', 'id')->all();
    $finalChemName = DB::table('chem_names')->pluck('name', 'id')->all();
    foreach ($catOfH as $h => $catId) {
        $report[] = [$h, cms3_clean($R[$h]['TenhoachatTA']), $cats[$catId]->code, $codeOf[$newCatOfH[$h]],
            $chemName[$oldChem[$h]], $finalChemName[$chemOfKey[$keyOf[$h]]], $labels($comps[$h]), $aiNote[$h],
            $labels($want[$keyOf[$h]]['components']),
            count($want[$keyOf[$h]]['sources']) > 1 ? 'Gộp từ ' . count($want[$keyOf[$h]]['sources']) . ' tên cũ' : ''];
    }
    $stats['chem_names_total'] = DB::table('chem_names')->count();
    $stats['categories_total'] = DB::table('chemical_categories')->count();
    $stats['dept_rows_total'] = DB::table('chemical_department_categories')->count();

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/6_rename_report.csv", ['Mã Access', 'TenhoachatTA', 'Mã DM cũ', 'Mã DM mới', 'Tên hoá chất cũ', 'Tên hoá chất mới',
    'Hoạt chất của mã Access', 'Kiểm hoạt chất (danh mục bị tách)', 'Thành phần tên mới', 'Ghi chú'], $report);
echo ($dry ? '[DRY - đã rollback] ' : '') . json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
