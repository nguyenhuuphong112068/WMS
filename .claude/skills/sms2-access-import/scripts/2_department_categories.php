<?php
/**
 * BƯỚC 2 - ĐỊNH KHU CHẤT CHUẨN + DANH MỤC CHẤT CHUẨN CỦA PHÒNG, từ Q_DMHC_QC1HH / Q_DMHC_QC2HH (SMS 2.0).
 *
 *   - Mã Access -> dòng danh mục công ty: đọc lịch sử "Thêm mới" của standard_category_histories (bước 1).
 *     Nhiều mã Access của cùng phòng rơi vào 1 dòng danh mục => 1 dòng danh mục phòng (unique phòng + danh mục):
 *     ĐVT / định khu / hạn dùng lấy giá trị dùng nhiều nhất, lệch thì báo cáo.
 *   - Định khu dựng theo bảng định khu Access của bộ phận (người dùng chọn 26/10/2026):
 *     Kho <BP>-CC -> Tủ = cột Tu (QC/INS-036, Tủ T10...) -> Cột (01, hoặc tủ con T7 trong "T7M2") -> Tầng -> Vị trí,
 *     item_type = 'standard'. Xem sms2_parse_zone().
 *   - unit_id = DVT; shelf_life_months = STHDNB (số tháng hạn dùng nội bộ, 0 -> trống); ngưỡng min/max ĐỂ TRỐNG
 *     (người dùng chọn); điều kiện bảo quản để trống (phòng theo danh mục công ty); note = GhiChu.
 *
 * CHẠY LẠI = xoá danh mục phòng + định khu do script tạo rồi dựng lại; dừng nếu đã có phiếu nhập chất chuẩn.
 * php 2_department_categories.php <thư mục JSON> [--dry]     Ra: <thư mục>/2_department_categories_report.csv
 */
require __DIR__ . '/_boot.php';

use App\Support\DataMasterHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$dir = rtrim($argv[1] ?? '', '/\\');
$dry = in_array('--dry', $argv, true);
$ACTOR = SMS2['actor'];
$now = now();

/** Phần mã tủ đưa vào code định khu: có số thì giữ (QC-INS-036, T10, 054), không thì viết tắt (TCPHC). */
function sms2_shelf_segment(string $tu): string
{
    $stripped = trim(preg_replace('/^tủ\s+/iu', '', $tu));
    if (preg_match('/\d/', $stripped)) {
        return trim(preg_replace('/[^A-Z0-9]+/', '-', strtoupper(Str::ascii($stripped))), '-');
    }
    $initials = '';
    foreach (preg_split('/\s+/u', Str::ascii($tu)) as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }
    return $initials;
}

/** Số hiệu tủ đứng đầu mã vị trí: "Tủ T10" -> T10, "QC/INS-036" -> 036, "Tủ 20" -> 20. */
function sms2_shelf_prefix(string $tu): ?string
{
    $stripped = strtoupper(trim(preg_replace('/^tủ\s+/iu', '', $tu)));
    if (preg_match('/^[A-Z]*\d+$/', $stripped)) {
        return $stripped;
    }
    return preg_match('/(\d+)\s*$/', $stripped, $m) ? $m[1] : null;
}

/**
 * Mã vị trí Access (đã bỏ số hiệu tủ) -> [cột, tầng, vị trí].
 *   K.BHA28 -> 01/K/BHA28   O7, A.1 -> 01/O/7   A, DT -> 01/A/1   1A, 2.DT -> 01/1/A
 *   T7M2 (tủ con T7) -> T7/M/2   còn lại -> 01/1/<cả mã>
 */
function sms2_parse_rest(string $rest): array
{
    $rest = ltrim($rest, '.');
    return match (true) {
        $rest === '' => ['01', '1', '1'],
        (bool) preg_match('/^([A-Z]+)\.?(\d+)$/', $rest, $m) => ['01', $m[1], $m[2]],
        (bool) preg_match('/^([A-Z]+)\.([A-Z0-9]+)$/', $rest, $m) => ['01', $m[1], $m[2]],
        (bool) preg_match('/^([A-Z]+)$/', $rest, $m) => ['01', $m[1], '1'],
        (bool) preg_match('/^(\d+)\.?([A-Z0-9]+)$/', $rest, $m) => ['01', $m[1], $m[2]],
        (bool) preg_match('/^([A-Z]+\d+)([A-Z]+)(\d+)$/', $rest, $m) => [$m[1], $m[2], $m[3]],
        default => ['01', '1', $rest],
    };
}

function sms2_seq_position(string $t): int
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

/* ---------- Đọc dữ liệu Access ---------- */
$deptRows = [];
foreach (sms2_json("$dir/dmhc_dept.json") as $r) {
    if (isset(SMS2['departments'][$r['Bophan']])) {
        $deptRows[$r['Bophan'] . '|' . $r['Machuan']] = $r; // query có thể lặp dòng giống hệt nhau
    }
}
$zoneOf = [];      // [BP][MÃ VỊ TRÍ] => tên tủ
$prefixes = [];    // [BP][số hiệu tủ] => tên tủ (suy tủ cho mã vị trí không có trong bảng định khu)
foreach (sms2_json("$dir/dinhkhu.json") as $z) {
    $code = mb_strtoupper(sms2_clean($z['MaViTri']));
    $tu = sms2_nullish($z['Tu']);
    if ($code === '' || $tu === null) {
        continue;
    }
    $zoneOf[$z['Bophan']][$code] ??= $tu;
    if ($p = sms2_shelf_prefix($tu)) {
        $prefixes[$z['Bophan']][$p] ??= $tu;
    }
}

/** Mã định khu Access -> [tên tủ, cột, tầng, vị trí, cách suy] hoặc null. */
$resolveZone = function (string $bp, string $raw) use ($zoneOf, $prefixes): ?array {
    $code = mb_strtoupper(sms2_clean($raw));
    if (sms2_nullish($code) === null) {
        return null;
    }
    $tu = $zoneOf[$bp][$code] ?? null;
    $how = 'bảng định khu';
    if ($tu === null) {
        // Không có trong bảng định khu: lấy tủ có số hiệu dài nhất khớp đầu mã (20BHA -> Tủ 20)
        $best = null;
        foreach ($prefixes[$bp] ?? [] as $p => $name) {
            if (str_starts_with($code, $p) && ($best === null || strlen($p) > strlen($best))) {
                $best = $p;
            }
        }
        if ($best === null) {
            return null;
        }
        $tu = $prefixes[$bp][$best];
        $how = 'suy theo số hiệu tủ';
    }
    $p = sms2_shelf_prefix($tu);
    if ($p !== null && str_starts_with($code, $p)) {
        $rest = substr($code, strlen($p));
    } else {
        // Số hiệu tủ trong mã lệch với tủ của bảng định khu (gõ nhầm 328E8 thuộc QC/INS-382, 036F.4 thuộc INS-037)
        // -> tin bảng định khu, bỏ phần số đứng đầu
        $rest = $p !== null ? preg_replace('/^\d+(?=[A-Z.])/', '', $code) : $code;
    }
    return [$tu, ...sms2_parse_rest($rest), $how];
};

$catOf = sms2_codes_from_history('standard_category_histories', 'standard_category_id');
$catCode = DB::table('standard_categories')->pluck('code', 'id');
$catVer = DB::table('standard_categories')->pluck('version', 'id');
$unitShort = DB::table('units')->pluck('short_name', 'id');

$stats = [];
$inc = function ($k, $n = 1) use (&$stats) { $stats[$k] = ($stats[$k] ?? 0) + $n; };

DB::beginTransaction();
try {
    /* ---------- 0. Dọn dữ liệu script tạo trước đó ---------- */
    // Tồn đầu kỳ (bước 3) trỏ vào danh mục phòng + định khu -> xoá cùng, chạy lại bước 2 thì chạy lại bước 3
    sms2_delete_stock($argv, $inc);
    $inc('cleanup_department_categories', DB::table('standard_department_categories')->where('created_by', $ACTOR)->delete());
    foreach (['locations', 'tiers', 'columns', 'shelves'] as $t) {
        $inc("cleanup_$t", DB::table($t)->where('created_by', $ACTOR)->delete());
    }
    $oldWh = DB::table('warehouses')->where('created_by', $ACTOR)->pluck('id');
    DB::table('datamaster_histories')->where('table_name', 'warehouses')->whereIn('record_id', $oldWh)->delete();
    $inc('cleanup_warehouses', DB::table('warehouses')->whereIn('id', $oldWh)->delete());

    /* ---------- 1. Gom mã Access theo (phòng, dòng danh mục) ---------- */
    $report = [];
    $groups = [];
    $line = fn ($r, $cat, $issue) => [$r['Bophan'], $r['Machuan'], $cat ? $catCode[$cat] : '', sms2_clean($r['Tenchuan']),
        sms2_clean($r['DVT']), sms2_clean($r['DinhKhu']), '', '', (int) $r['STHDNB'] ?: '', $issue];
    foreach ($deptRows as $r) {
        $cat = $catOf[$r['Machuan']] ?? null;
        if (! $cat) {
            $report[] = $line($r, null, 'Bỏ qua: mã chưa có trong danh mục công ty');
            $inc('skip_no_category');
            continue;
        }
        $groups[$r['Bophan'] . '|' . $cat][] = $r;
    }

    /* ---------- 2. Định khu: gom hết vị trí trước để đánh số vị trí chữ (BHA28, GN) sau vị trí số ---------- */
    $zoneOfRow = [];
    $tierPositions = [];
    foreach ($deptRows as $r) {
        $z = $resolveZone($r['Bophan'], (string) $r['DinhKhu']);
        $zoneOfRow[$r['Bophan'] . '|' . $r['Machuan']] = $z;
        if ($z) {
            [$tu, $col, $tier, $pos] = $z;
            $tierPositions[$r['Bophan'] . '|' . $tu . '|' . $col . '|' . $tier][$pos] = 1;
        }
    }
    $posNumber = [];
    foreach ($tierPositions as $tk => $list) {
        $list = array_keys($list);
        $max = max(array_map(fn ($p) => ctype_digit((string) $p) ? (int) $p : 0, $list));
        natsort($list);
        foreach ($list as $p) {
            $posNumber[$tk . '|' . $p] = ctype_digit((string) $p) ? (int) $p : ++$max;
        }
    }

    $ids = ['wh' => [], 'shelf' => [], 'col' => [], 'tier' => [], 'loc' => []];
    $base = fn ($dept) => ['department_id' => $dept, 'status_id' => 1, 'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now];
    $locationFor = function (string $bp, array $z) use (&$ids, $base, $pad, $inc, $posNumber): array {
        [$tu, $col, $tier, $pos] = $z;
        $cfg = SMS2['departments'][$bp];
        $dept = $cfg['id'];
        if (! isset($ids['wh'][$bp])) {
            $ids['wh'][$bp] = DB::table('warehouses')->insertGetId($base($dept) + ['code' => $cfg['warehouse_code'], 'name' => $cfg['warehouse_name']]);
            DataMasterHistory::record('warehouses', $ids['wh'][$bp], 'Thêm mới',
                'Nạp định khu chất chuẩn từ SMS 2.0 (' . $cfg['zone_table'] . ').', ['code' => 'Mã', 'name' => 'Tên']);
            $inc('warehouses_new');
        }
        $wh = $ids['wh'][$bp];
        $sCode = $cfg['warehouse_code'] . '/' . sms2_shelf_segment($tu);
        if (! isset($ids['shelf'][$sCode])) {
            $ids['shelf'][$sCode] = DB::table('shelves')->insertGetId($base($dept) + ['code' => $sCode, 'name' => $tu, 'warehouse_id' => $wh]);
            $inc('shelves_new');
        }
        $cCode = $sCode . '/' . $pad($col);
        if (! isset($ids['col'][$cCode])) {
            $ids['col'][$cCode] = DB::table('columns')->insertGetId($base($dept) + ['code' => $cCode,
                'name' => ($col === '01' ? 'Cột ' : 'Tủ con ') . $pad($col), 'warehouse_id' => $wh,
                'shelf_id' => $ids['shelf'][$sCode], 'position' => sms2_seq_position(preg_replace('/^[A-Z]+/', '', $col) ?: $col)]);
            $inc('columns_new');
        }
        $tCode = $cCode . '/' . $pad($tier);
        if (! isset($ids['tier'][$tCode])) {
            $ids['tier'][$tCode] = DB::table('tiers')->insertGetId($base($dept) + ['code' => $tCode, 'name' => 'Tầng ' . $pad($tier),
                'warehouse_id' => $wh, 'shelf_id' => $ids['shelf'][$sCode], 'column_id' => $ids['col'][$cCode],
                'position' => sms2_seq_position($tier)]);
            $inc('tiers_new');
        }
        $lCode = $tCode . '/' . $pad($pos);
        if (mb_strlen($lCode) > 50) {
            throw new RuntimeException("Mã định khu quá 50 ký tự: $lCode");
        }
        if (! isset($ids['loc'][$lCode])) {
            $ids['loc'][$lCode] = DB::table('locations')->insertGetId($base($dept) + ['code' => $lCode, 'warehouse_id' => $wh,
                'shelf_id' => $ids['shelf'][$sCode], 'column_id' => $ids['col'][$cCode], 'tier_id' => $ids['tier'][$tCode],
                'position' => $posNumber[$bp . '|' . $tu . '|' . $col . '|' . $tier . '|' . $pos], 'item_type' => 'standard', 'zone_type' => null]);
            $inc('locations_new');
        }
        return [$ids['loc'][$lCode], $lCode];
    };

    /* ---------- 3. Danh mục chất chuẩn của phòng ---------- */
    foreach ($groups as $key => $list) {
        [$bp, $cat] = explode('|', $key);
        $cat = (int) $cat;
        $dept = SMS2['departments'][$bp]['id'];
        $issues = [];

        $units = array_map(fn ($r) => sms2_unit_id($r['DVT']), $list);
        $unit = sms2_mode($units);
        if ($unit === null) {
            foreach ($list as $r) {
                $report[] = $line($r, $cat, 'Bỏ qua: ĐVT "' . sms2_clean($r['DVT']) . '" chưa có trong Dữ Liệu Gốc');
            }
            $inc('skip_no_unit');
            continue;
        }
        $unit = (int) $unit;
        if (count(array_unique(array_filter($units))) > 1) {
            $issues[] = 'Nhiều ĐVT cùng nhóm, đã lấy ' . $unitShort[$unit];
        }

        $locs = [];
        $unplaced = [];
        foreach ($list as $r) {
            $z = $zoneOfRow[$bp . '|' . $r['Machuan']];
            if ($z) {
                $locs[] = $locationFor($bp, $z);
                if ($z[4] !== 'bảng định khu') {
                    $issues[] = 'Định khu ' . sms2_clean($r['DinhKhu']) . ' ' . $z[4];
                }
            } elseif (sms2_nullish($r['DinhKhu']) !== null) {
                $unplaced[] = sms2_clean($r['DinhKhu']);
            }
        }
        $locId = sms2_mode(array_column($locs, 0));
        $locCode = $locId ? array_column($locs, 1, 0)[$locId] : '';
        if (count(array_unique(array_column($locs, 0))) > 1) {
            $issues[] = 'Định khu lệch giữa các mã gộp';
        }
        if ($unplaced) {
            $issues[] = 'Định khu chưa dựng được: ' . implode(', ', array_unique($unplaced));
        }

        $shelf = sms2_mode(array_map(fn ($r) => (int) $r['STHDNB'] ?: null, $list));
        if (count(array_unique(array_map(fn ($r) => (int) $r['STHDNB'], $list))) > 1) {
            $issues[] = 'Hạn dùng nội bộ lệch giữa các mã gộp';
        }

        $notes = [];
        foreach ($list as $r) {
            $g = sms2_clean($r['GhiChu']);
            if ($g !== '' && ! in_array(mb_strtoupper($g), ['NA', '0', 'TEST', '-'], true)) {
                $notes[$g] = 1;
            }
            if ((int) explode('.', (string) $r['Ver'])[0] !== (int) $catVer[$cat]) {
                $issues[] = 'Phòng đang dùng Ver ' . $r['Ver'] . ' (' . $r['Machuan'] . '), danh mục công ty Ver ' . $catVer[$cat];
            }
        }
        if ($unplaced) {
            $notes['Định khu SMS 2.0: ' . implode(', ', array_unique($unplaced))] = 1;
        }
        $note = $notes ? mb_substr(implode('; ', array_keys($notes)), 0, 500) : null;

        DB::table('standard_department_categories')->insert(['department_id' => $dept, 'category_id' => $cat,
            'unit_id' => $unit, 'shelf_life_months' => $shelf === null ? null : (int) $shelf,
            'default_location_id' => $locId ? (int) $locId : null, 'storage_condition_id' => null,
            'min_stock' => null, 'max_stock' => null, 'note' => $note, 'status_id' => 1,
            'created_by' => $ACTOR, 'created_at' => $now, 'updated_at' => $now]);
        $inc("dept_categories_$bp");
        $inc('access_codes_used', count($list));
        if (count($list) > 1) {
            $issues[] = 'Gộp ' . count($list) . ' mã';
            $inc('dept_categories_merged');
        }
        if (! $locId) {
            $inc('dept_categories_no_location');
        }

        foreach ($list as $r) {
            $row = $line($r, $cat, implode(' | ', array_unique($issues)));
            $row[6] = $unitShort[$unit];
            $row[7] = $locCode;
            $report[] = $row;
        }
    }

    sms2_write_csv("$dir/2_department_categories_report.csv",
        ['Bộ phận', 'Mã Access', 'Mã WMS', 'Tên chuẩn', 'ĐVT Access', 'Định khu Access', 'ĐVT WMS', 'Định khu WMS',
         'Hạn dùng nội bộ (tháng)', 'Cần rà'], $report);

    $stats['access_rows'] = count($deptRows);
    ksort($stats);
    foreach ($stats as $k => $v) {
        printf("  %-32s %6d\n", $k, $v);
    }

    if ($dry) {
        DB::rollBack();
        echo "DRY RUN - đã rollback. Báo cáo: $dir/2_department_categories_report.csv\n";
    } else {
        DB::commit();
        echo "ĐÃ GHI. Báo cáo: $dir/2_department_categories_report.csv\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
