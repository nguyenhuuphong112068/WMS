<?php
/**
 * BƯỚC 1 - DỮ LIỆU GỐC "TÊN HOÁ CHẤT" (chem_names) từ dmhc_chung.json (Q_DMHC_ChungHH).
 *
 * Quy tắc (user đã chốt):
 *   - MỌI tên hoá chất phải liên kết >= 1 hoạt chất (chem_name_active_ingredient), cả đơn chất lẫn hỗn hợp.
 *   - Đơn chất: tên hoá chất = tên hoạt chất (gộp mọi dòng Access cùng hoạt chất).
 *   - Hỗn hợp (>= 2 thành phần): tên = TenhoachatTA, % lấy từ cột Tenhoatchat.
 *   - Khớp thành phần: CAS -> tên -> CAS tra theo tên ở dòng Access khác -> mục gộp (từ khoá + cờ PL)
 *     -> tạo hoạt chất ngoài danh mục (is_statutory=0, ghi nhóm theo cờ PL nếu là đơn chất).
 *   - Access xếp nhóm hỗn hợp (2/8/10) mà chỉ có 1 CAS => dung dịch pha loãng = hoạt chất + "Nước".
 *   - Nhóm 10: tick mặc định Bảng B CMS3['table_b_default_code'] cho hỗn hợp có thành phần nhóm 9.
 *
 * CHẠY LẠI = XOÁ SẠCH chem_names + danh mục HC công ty + danh mục phòng + phiếu nhập do bộ script tạo.
 * Dừng lại nếu đã có dữ liệu nghiệp vụ thật (phiếu xuất, phiếu nhập/danh mục do người dùng tạo).
 *
 * php 1_chem_names.php <thư mục JSON> [--dry]
 * Ra: <thư mục>/1_chem_names_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\ChemicalClassification as CC;
use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = CMS3['actor'];
$now = now();

function nkey($s): string
{
    $s = mb_strtolower(cms3_clean($s));
    $s = preg_replace('/\b(anhydrous|for analysis|extra pure|solution|dung dịch)\b/u', '', $s);
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $s);
}

function maxPercent(string $line): ?float
{
    if (! str_contains($line, '%')) {
        return null;
    }
    $part = preg_match('/:(.*)$/u', $line, $mm) ? $mm[1] : $line;
    preg_match_all('/(\d+(?:[.,]\d+)?)\s*(?=[^%\d]*%)/u', $part, $m);
    $vals = array_filter(array_map(fn ($v) => (float) str_replace(',', '.', $v), $m[1]), fn ($v) => $v > 0 && $v <= 100);
    return $vals ? max($vals) : null;
}

/** Tên thành phần trên 1 dòng: cắt ở ":" / dấu so sánh / nồng độ sau khoảng trắng (không cắt "1-Octane", "2,6-..."). */
function lineName(string $line): string
{
    $name = preg_replace('/(\s*:|\s*[≥≤<>=]|\s+\d+(?:[.,]\d+)?\s*(?:[-–]\s*\d+(?:[.,]\d+)?\s*)?%).*$/u', '', cms3_clean($line));
    return cms3_clean(trim($name, " ,;.-"));
}

/** Bỏ nồng độ ĐỨNG CUỐI tên ("... 10-15 %", "... 0.1 N"), không cắt "Sabouraud 4% dextrose agar". */
function stripConcentration(string $name): string
{
    $out = preg_replace('/\s+\d+(?:[.,]\d+)?\s*(?:[-–]\s*\d+(?:[.,]\d+)?\s*)?(%|N|M|mol\/l|mol|ppm|mg\/l|g\/l)\s*(solution|dung dịch)?\s*$/iu', '', cms3_clean($name));
    $out = preg_replace('/\s*(solution|dung dịch)\s*$/iu', '', $out);
    return cms3_clean($out) !== '' ? cms3_clean($out) : cms3_clean($name);
}

$rows = array_values(array_filter(cms3_unique_rows(cms3_json("$dir/dmhc_chung.json")), fn ($r) => ! cms3_is_test_row($r)));

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };
$report = [];
$row2key = [];

DB::beginTransaction();
try {
    /* ---------- 0. Chốt an toàn + xoá dữ liệu cũ ---------- */
    $userImports = DB::table('chemical_imports')->where('created_by', '<>', $ACTOR)->count();
    $exports = DB::table('chemical_exports')->count();
    $userCats = DB::table('chemical_categories')->where('created_by', '<>', $ACTOR)->count();
    if (($userImports || $exports || $userCats) && ! in_array('--force', $argv, true)) {
        throw new RuntimeException("Đã có dữ liệu nghiệp vụ thật (phiếu nhập người dùng: $userImports, phiếu xuất: $exports, "
            . "danh mục người dùng tạo: $userCats). Không xoá. Thêm --force nếu chắc chắn.");
    }
    $imp = DB::table('chemical_imports')->where('created_by', $ACTOR)->pluck('id');
    DB::table('chemical_import_histories')->whereIn('import_id', $imp)->delete();
    DB::table('chemical_imports')->whereIn('id', $imp)->delete();
    DB::table('chemical_department_categories')->where('created_by', $ACTOR)->delete();
    $stats['deleted_categories'] = DB::table('chemical_categories')->count();
    $stats['deleted_chem_names'] = DB::table('chem_names')->count();
    DB::table('chemical_category_histories')->delete();
    DB::table('chemical_categories')->delete();
    DB::table('chem_name_active_ingredient')->delete();
    DB::table('chem_name_mixture_hazard_category')->delete();
    DB::table('datamaster_histories')->where('table_name', 'chem_names')->delete();
    DB::table('chem_names')->delete();
    $oldAi = DB::table('active_ingredients')->where('created_by', $ACTOR)->pluck('id');
    $stats['deleted_old_import_ai'] = $oldAi->count();
    DB::table('active_ingredient_classifications')->whereIn('active_ingredients_id', $oldAi)->delete();
    DB::table('datamaster_histories')->where('table_name', 'active_ingredients')->whereIn('record_id', $oldAi)->delete();
    DB::table('active_ingredients')->whereIn('id', $oldAi)->delete();

    /* ---------- Chỉ mục hoạt chất ---------- */
    $ais = DB::table('active_ingredients')->where('status_id', 1)->where('app_status', 'approved')
        ->orderBy('id')->get(['id', 'name', 'name_en', 'cas_no', 'is_collective']);
    $aiGroups = CC::groupsForActiveIngredients($ais->pluck('id')->all());
    $collective = $ais->pluck('is_collective', 'id')->all();
    $aiName = [];
    $byCas = [];
    $byName = [];
    foreach ($ais as $a) {
        $aiName[$a->id] = $a->name;
        foreach (cms3_cas_list($a->cas_no) as $cas) {
            $byCas[$cas][] = (int) $a->id;
        }
        foreach ([$a->name, $a->name_en] as $n) {
            if (($k = nkey($n)) !== '') {
                $byName[$k][] = (int) $a->id;
            }
        }
    }
    $usedAiNames = array_flip(array_map('mb_strtolower', $aiName));

    // Từ điển từ chính dữ liệu Access: CAS -> tên (đặt tên hoạt chất tạo mới) và tên -> CAS (thành phần không ghi CAS)
    $casName = [];
    $casByName = [];
    foreach ($rows as $r) {
        $cs = cms3_cas_list($r['CASnumber']);
        $ls = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $r['Tenhoatchat']))));
        if (count($cs) === 1) {
            $en = cms3_clean(explode(';', (string) $r['TenhoachatTA'])[0]);
            $casName[$cs[0]]['en'] ??= stripConcentration($en);
            $casName[$cs[0]]['vi'] ??= stripConcentration(cms3_clean(explode(';', (string) $r['TenhoachatTV'])[0]));
            foreach (array_merge(explode(';', (string) $r['TenhoachatTA']), explode(';', (string) $r['TenhoachatTV']), $ls ? [lineName($ls[0])] : []) as $n) {
                if (($k = nkey(stripConcentration($n))) !== '') {
                    $casByName[$k] ??= $cs[0];
                }
            }
        } elseif (count($cs) >= 2 && count($cs) === count($ls)) {
            foreach ($cs as $i => $c) {
                if (($n = lineName($ls[$i])) !== '') {
                    $casName[$c]['en'] ??= $n;
                    $casByName[nkey($n)] ??= $c;
                }
            }
        }
    }

    $pick = function (array $ids, array $accG) use (&$aiGroups, &$collective) {
        $ids = array_values(array_unique($ids));
        usort($ids, function ($a, $b) use ($aiGroups, $accG, $collective) {
            $sa = count(array_intersect($aiGroups[$a] ?? [], $accG));
            $sb = count(array_intersect($aiGroups[$b] ?? [], $accG));
            return [$sb, (int) ($collective[$a] ?? 0), $a] <=> [$sa, (int) ($collective[$b] ?? 0), $b];
        });
        return $ids[0];
    };
    $nextAiCode = fn () => 'A' . str_pad((string) (DB::table('active_ingredients')->where('code', 'like', 'A%')->pluck('code')
        ->map(fn ($c) => (int) substr($c, 1))->max() + 1), 5, '0', STR_PAD_LEFT);

    $createAi = function (string $name, string $nameEn, ?string $cas, ?string $formula, array $accG, string $context, string $allNames)
        use (&$byCas, &$byName, &$aiName, &$usedAiNames, &$aiGroups, &$collective, $nextAiCode, $ACTOR, $now, $inc) {
        $name = stripConcentration($name);
        $nameEn = stripConcentration($nameEn);
        $parentId = null;
        $best = 0;
        foreach (CMS3['collectives'] as $pid => $re) {
            if (preg_match($re, $name . ' ' . $nameEn . ' ' . $allNames)) {
                $overlap = count(array_intersect($aiGroups[$pid] ?? [], $accG));
                if ($overlap > $best) {
                    [$best, $parentId] = [$overlap, $pid];
                }
            }
        }
        // Trùng tên (thường do TenhoachatTV ghi sai) -> dùng tên tiếng Anh, còn trùng mới gắn CAS
        if (isset($usedAiNames[mb_strtolower($name)]) && $nameEn !== '' && ! isset($usedAiNames[mb_strtolower($nameEn)])) {
            $name = $nameEn;
        }
        $name = mb_substr($name, 0, 230);
        if (isset($usedAiNames[mb_strtolower($name)])) {
            $name .= $cas ? ' (CAS ' . $cas . ')' : ' (CMS)';
        }
        $id = DB::table('active_ingredients')->insertGetId([
            'parent_id' => $parentId, 'code' => $nextAiCode(), 'name' => $name, 'name_en' => mb_substr($nameEn, 0, 255) ?: null,
            'cas_no' => $cas, 'chemical_formula' => $formula ? mb_substr($formula, 0, 255) : null,
            'equiv_factor' => 1, 'is_collective' => 0, 'is_statutory' => 0, 'legal_ref' => 'Ngoài danh mục NĐ 24/2026 (nạp từ CMS 3.0)',
            'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $own = array_values(array_diff(array_intersect($accG, CC::SINGLE_SUBSTANCE_GROUPS), $parentId ? ($aiGroups[$parentId] ?? []) : []));
        foreach ($own as $g) {
            [$appendix, $groupNo, $tableRef] = CC::tripleOf($g);
            DB::table('active_ingredient_classifications')->insert(['active_ingredients_id' => $id, 'appendix' => $appendix,
                'group_no' => $groupNo, 'table_ref' => $tableRef, 'note' => 'Theo phân loại CMS 3.0', 'is_statutory' => 0,
                'created_at' => $now, 'updated_at' => $now]);
        }
        DataMasterHistory::record('active_ingredients', $id, 'Thêm mới',
            'Nạp từ CMS 3.0 (' . $context . '): ' . $name . '.' . ($parentId ? ' Thuộc mục gộp: ' . $aiName[$parentId] . '.' : '')
                . ($own ? ' Phân loại: nhóm ' . implode(', ', $own) . '.' : ''),
            ['name' => 'Tên hoạt chất', 'name_en' => 'Tên tiếng Anh', 'cas_no' => 'Số CAS', 'chemical_formula' => 'Công thức hoá học', 'parent_id' => 'Thuộc mục gộp']);
        $aiName[$id] = $name;
        $usedAiNames[mb_strtolower($name)] = 1;
        $collective[$id] = 0;
        $aiGroups[$id] = array_values(array_unique(array_merge($own, $parentId ? ($aiGroups[$parentId] ?? []) : [])));
        sort($aiGroups[$id]);
        if ($cas) {
            $byCas[$cas] = [$id];
        }
        foreach ([$name, $nameEn] as $n) {
            if (($k = nkey($n)) !== '') {
                $byName[$k] ??= [$id];
            }
        }
        $inc($parentId ? 'ai_new_in_collective' : 'ai_new');
        return [$id, $parentId ? 'tạo mới, thuộc mục gộp' : 'tạo mới'];
    };

    $resolve = function (?string $cas, array $names, array $accG, ?string $formula, string $nameVi, string $context)
        use (&$byCas, &$byName, $pick, $createAi, $casName, $casByName) {
        if ($cas && ! $names && isset($casName[$cas]['en'])) {
            $names = [$casName[$cas]['en']];
        }
        if ($cas && $nameVi === '' && isset($casName[$cas]['vi'])) {
            $nameVi = $casName[$cas]['vi'];
        }
        if ($cas && isset($byCas[$cas])) {
            return [$pick($byCas[$cas], $accG), 'CAS'];
        }
        foreach ($names as $n) {
            foreach (array_map('trim', explode(';', $n)) as $alt) {
                if (($k = nkey($alt)) !== '' && isset($byName[$k])) {
                    return [$pick($byName[$k], $accG), 'tên'];
                }
            }
        }
        // Không ghi CAS nhưng tên trùng 1 dòng Access khác có CAS (vd "Hydrochloric acid" trong "Cleaning")
        if (! $cas) {
            foreach ($names as $n) {
                $k = nkey(stripConcentration($n));
                if ($k !== '' && isset($casByName[$k]) && isset($byCas[$casByName[$k]])) {
                    return [$pick($byCas[$casByName[$k]], $accG), 'CAS theo tên'];
                }
            }
        }
        $nameEn = cms3_clean($names[0] ?? '') ?: ('CAS ' . $cas);
        return $createAi($nameVi !== '' ? $nameVi : $nameEn, $nameEn, $cas, $formula, $accG, $context, implode(' ', $names) . ' ' . $nameVi);
    };

    [$waterId] = $resolve('7732-18-5', ['Water'], [], 'H2O', 'Nước', 'dung môi');

    /* ---------- 1-2. Thành phần từng dòng -> hoạt chất ---------- */
    $chem = [];
    foreach ($rows as $r) {
        $ta = cms3_clean($r['TenhoachatTA']);
        if ($ta === '') {
            $report[] = [$r['Mahoachat'], '', '', '', cms3_clean($r['CASnumber']), '', '', '', '', 'Bỏ qua: không có tên hoá chất'];
            continue;
        }
        $accG = cms3_access_groups($r);
        $tokens = array_values(array_filter(array_map('trim', preg_split('/[,;\r\n]+/', (string) $r['CASnumber'])), fn ($t) => $t !== ''));
        $casTokens = array_values(array_unique(array_filter($tokens, fn ($t) => (bool) preg_match('/^\d{2,7}-\d{2}-\d$/', $t))));
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $r['Tenhoatchat'])),
            fn ($l) => $l !== '' && ! preg_match('/^(na|n\/a|-)$/i', cms3_clean($l))));
        $formulas = array_values(array_filter(array_map('trim', preg_split('/\r?\n|,\s*/', (string) $r['CTHH']))));

        $comps = []; // [cas|null, [tên], %, công thức]
        if ($casTokens) {
            $pair = count($tokens) === count($lines) && count($lines) >= 2;
            $list = $pair ? $tokens : $casTokens;
            foreach ($list as $i => $tok) {
                $cas = preg_match('/^\d{2,7}-\d{2}-\d$/', $tok) ? $tok : null;
                $line = $pair ? $lines[$i] : (count($casTokens) === count($lines) ? $lines[$i] : null);
                $names = $line !== null ? [lineName($line)] : [];
                if (count($list) === 1) {
                    $names[] = $ta;
                }
                $comps[] = [$cas, array_values(array_filter($names)), $line !== null && count($list) >= 2 ? maxPercent($line) : null,
                    count($formulas) === count($list) ? $formulas[$i] : (count($list) === 1 ? ($formulas[0] ?? null) : null)];
            }
        } elseif (count($lines) >= 2) {
            foreach ($lines as $line) {
                if (($n = lineName($line)) !== '') {
                    $comps[] = [null, [$n], maxPercent($line), null];
                }
            }
        } else {
            $comps[] = [null, array_values(array_filter([$lines ? lineName($lines[0]) : '', $ta])), null, $formulas[0] ?? null];
        }

        $isMixture = count($comps) >= 2;
        $linked = [];
        $how = [];
        foreach ($comps as [$cas, $names, $pct, $formula]) {
            [$aiId, $method] = $resolve($cas, $names, $isMixture ? [] : $accG, $formula, $isMixture ? '' : cms3_clean($r['TenhoachatTV']),
                ($isMixture ? 'thành phần của ' . $ta : $ta) . ', mã ' . $r['Mahoachat']);
            if (! isset($linked[$aiId])) {
                $linked[$aiId] = $pct;
                $how[] = $method;
            }
        }
        // Dung dịch pha loãng: Access xếp nhóm hỗn hợp mà chỉ ra 1 hoạt chất
        if (count($linked) === 1 && array_intersect($accG, CC::MIXTURE_GROUPS) && array_key_first($linked) !== $waterId) {
            $main = array_key_first($linked);
            $linked[$main] = preg_match('/(\d+(?:[.,]\d+)?)\s*%/u', $ta, $pm) ? min(100.0, (float) str_replace(',', '.', $pm[1])) : null;
            $linked[$waterId] = null;
            $how[] = 'dung dịch (+ Nước theo phân loại)';
            $inc('dilute_solution_rows');
        }
        $isMixture = count($linked) >= 2;
        $key = $isMixture ? 'mix:' . mb_strtolower($ta) : 'ai:' . array_key_first($linked);
        $name = $isMixture ? mb_substr($ta, 0, 255) : $aiName[array_key_first($linked)];
        if (! isset($chem[$key]) || ($isMixture && count($linked) > count($chem[$key]['components']))) {
            $chem[$key] = ['name' => $name, 'mixture' => $isMixture, 'components' => $linked,
                'rows' => $chem[$key]['rows'] ?? [], 'banned' => $chem[$key]['banned'] ?? false, 'pl10' => $chem[$key]['pl10'] ?? false];
        }
        $chem[$key]['rows'][] = $r['Mahoachat'];
        $chem[$key]['banned'] = $chem[$key]['banned'] || ! empty($r['HC_Cam']);
        $chem[$key]['pl10'] = $chem[$key]['pl10'] || ! empty($r['PL_4_B']);
        $row2key[] = [$key, $r, $accG, implode(', ', $how)];
    }

    /* ---------- 3. Ghi chem_names + liên kết + Bảng B mặc định ---------- */
    $tableB = CMS3['table_b_default_code'] ? DB::table('mixture_hazard_categories')->where('code', CMS3['table_b_default_code'])->value('id') : null;
    $used = [];
    $chemId = [];
    foreach ($chem as $key => $c) {
        $name = $c['name'];
        if (isset($used[mb_strtolower($name)])) {
            $name = mb_substr($name, 0, 240) . ' (' . $c['rows'][0] . ')';
        }
        $used[mb_strtolower($name)] = 1;
        $id = DB::table('chem_names')->insertGetId(['name' => $name, 'is_banned' => $c['banned'] ? 1 : 0, 'is_special_goods' => 0,
            'app_status' => 'approved', 'status_id' => 1, 'created_by' => $ACTOR, 'approved_by' => $ACTOR, 'approved_at' => $now,
            'created_at' => $now, 'updated_at' => $now]);
        foreach ($c['components'] as $aiId => $pct) {
            DB::table('chem_name_active_ingredient')->insert(['chem_names_id' => $id, 'active_ingredients_id' => $aiId,
                'content_percent' => $c['mixture'] ? $pct : null, 'created_at' => $now, 'updated_at' => $now]);
        }
        $ticked = false;
        if ($tableB && $c['pl10'] && $c['mixture'] && array_filter(array_keys($c['components']), fn ($ai) => in_array(9, $aiGroups[$ai] ?? [], true))) {
            DB::table('chem_name_mixture_hazard_category')->insert(['chem_names_id' => $id, 'mixture_hazard_categories_id' => $tableB,
                'created_at' => $now, 'updated_at' => $now]);
            $ticked = true;
            $inc('table_b_default_ticked');
        }
        $labels = implode('; ', array_map(fn ($ai, $p) => $aiName[$ai] . ($p !== null && $c['mixture'] ? ' (' . rtrim(rtrim(number_format($p, 4, '.', ''), '0'), '.') . '%)' : ''),
            array_keys($c['components']), $c['components']));
        DataMasterHistory::record('chem_names', $id, 'Thêm mới',
            'Nạp từ CMS 3.0 (mã ' . implode(', ', $c['rows']) . '): ' . $name . '. ' . ($c['mixture'] ? 'Hỗn hợp' : 'Đơn chất') . ' - hoạt chất: ' . $labels . '.'
                . ($ticked ? ' Nhóm 10: tick MẶC ĐỊNH Bảng B ' . CMS3['table_b_default_code'] . ' - cần xác nhận lại.' : ''),
            ['name' => 'Tên hoá chất', 'is_banned' => 'Hoá chất cấm (Luật Đầu tư 2025)', 'is_special_goods' => 'Hàng hoá đặc biệt'],
            ['is_banned' => [0 => 'Không', 1 => 'Có'], 'is_special_goods' => [0 => 'Không', 1 => 'Có']]);
        $chemId[$key] = $id;
        $inc($c['mixture'] ? 'chem_names_mixture' : 'chem_names_single');
        $inc('ai_links', count($c['components']));
    }

    /* ---------- 4. Báo cáo đối chiếu ---------- */
    $derived = CC::groupsByChemName(array_values($chemId));
    $names = DB::table('chem_names')->pluck('name', 'id');
    foreach ($row2key as [$key, $r, $accG, $how]) {
        $cid = $chemId[$key];
        $acc = $accG;
        if (! empty($r['HC_Cam'])) {
            $acc[] = 11;
        }
        sort($acc);
        $der = array_values(array_filter($derived[$cid] ?? [], fn ($g) => $g <= 11));
        $notes = [];
        if ($acc != $der) {
            $notes[] = 'LỆCH nhóm';
        }
        if (in_array(10, $acc, true) && ! in_array(10, $der, true)) {
            $notes[] = 'Nhóm 10 chưa suy được (hỗn hợp cần thành phần nhóm 9)';
        }
        $report[] = [$r['Mahoachat'], cms3_clean($r['TenhoachatTA']), $names[$cid], $chem[$key]['mixture'] ? 'Hỗn hợp' : 'Đơn chất',
            cms3_clean($r['CASnumber']), implode(', ', array_map(fn ($ai) => $aiName[$ai], array_keys($chem[$key]['components']))),
            $how, implode(',', $acc), implode(',', $der), implode(' | ', $notes)];
    }

    $orphan = DB::table('chem_names')->whereNotIn('id', DB::table('chem_name_active_ingredient')->select('chem_names_id'))->count();
    if ($orphan) {
        throw new RuntimeException("$orphan tên hoá chất chưa liên kết hoạt chất - vi phạm quy tắc");
    }
    $stats['group_mismatch_rows'] = count(array_filter($report, fn ($l) => str_contains(end($l), 'LỆCH')));

    $dry ? DB::rollBack() : DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    throw $e;
}

cms3_write_csv("$dir/1_chem_names_report.csv", ['Mã Access', 'Tên Access (TA)', 'Tên hoá chất WMS', 'Loại', 'CAS', 'Hoạt chất liên kết',
    'Cách khớp', 'Nhóm theo Access', 'Nhóm WMS suy được', 'Ghi chú'], $report);
$stats['mode'] = $dry ? 'DRY-RUN (rollback)' : 'COMMITTED';
print_r($stats);
