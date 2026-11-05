<?php
/**
 * Khởi động Laravel + cấu hình + hàm dùng chung cho các script nạp dữ liệu CMS 3.0 (Access).
 * Mọi script: require __DIR__ . '/_boot.php';
 *
 * Sửa CMS3 ở đây khi đổi phòng ban / kho / mặc định - KHÔNG sửa rải rác trong từng script.
 */

$root = dirname(__DIR__, 4); // .claude/skills/cms3-access-import/scripts -> gốc dự án
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const CMS3 = [
    // Người ghi created_by/approved_by. Dùng để nhận ra (và xoá khi chạy lại) dữ liệu do bộ script tạo.
    'actor' => 'Import CMS 3.0 (system)',

    // Mabophan Access => [id phòng WMS (deparments.id), mã kho hoá chất, tên kho]
    'departments' => [
        'QC1' => ['id' => 20, 'warehouse_code' => 'QC1-HC', 'warehouse_name' => 'Kho Hoá Chất QC1'],
        'QC2' => ['id' => 9, 'warehouse_code' => 'QC2-HC', 'warehouse_name' => 'Kho Hoá Chất QC2'],
        // Thêm 04/11/2026 bằng 7_add_departments.php (Q_DMHC_QC3HH / Q_DMHC_QC4HH)
        'QC' => ['id' => 15, 'warehouse_code' => 'QCNM1-HC', 'warehouse_name' => 'Kho Hoá Chất QC NM1'],
        'AD' => ['id' => 10, 'warehouse_code' => 'PTPT-HC', 'warehouse_name' => 'Kho Hoá Chất PTPT'],
    ],

    // DVT Access viết khác tên đơn vị trong units (khoá viết thường) - UN = đơn vị hoạt tính enzyme
    'unit_aliases' => ['un' => 'u'],

    // Cờ phụ lục Access => nhóm NĐ 24/2026 (PL_1_1 KHÔNG dùng). HC_Cam => chem_names.is_banned
    'pl_flags' => ['PL_2_1' => 1, 'PL_2_2' => 2, 'PL_3_1_A' => 3, 'PL_3_1_B' => 4, 'PL_3_2_A' => 5,
                   'PL_3_2_B' => 6, 'PL_3_2_C' => 7, 'PL_3_3' => 8, 'PL_4_A' => 9, 'PL_4_B' => 10],

    // Nhóm 10: Access không cho biết mục Bảng B nào -> tick mặc định mục này (user sửa sau). null = không tick.
    'table_b_default_code' => 'B00001',

    // Mục gộp hoạt chất: id active_ingredients (is_collective=1) => từ khoá tên (tiếng Anh / Việt).
    // Chỉ gắn khi tên khớp VÀ cờ PL Access trùng nhóm của mục gộp. Kiểm tra lại id khi đổi CSDL.
    'collectives' => [
        248 => '/mercury|thuỷ ngân|thủy ngân/iu',
        3034 => '/\blead\b|\bplumb|chì/iu',
        3043 => '/chromate|dichromate|chromium\s*\(?vi\)?|cr6/iu',
        281 => '/cyanide|cyanoferrate|ferricyanide|ferrocyanide|nitroprusside|nitroferricyanide/iu',
        3000 => '/arsen/iu',
        187 => '/nickel|nicken|niken/iu',
        84 => '/cobalt|coban/iu',
        49 => '/beryll/iu',
    ],

    // Đơn vị Access chưa có trong units -> tạo mới nhóm count. Khoá = DVT viết thường.
    'new_units' => ['bản' => ['Bản', 'Bản (tấm)'], 'que' => ['Que', 'Que'], 'thẻ' => ['Thẻ', 'Thẻ'],
                    'u' => ['U', 'Đơn vị hoạt tính (U)'], 'cap' => ['CAP', 'Viên nang (CAP)']],

    // Ngưỡng tồn min/max khi Access không có: sinh ngẫu nhiên (seed cố định để chạy lại ra cùng số).
    'random_seed' => 20261022,
];

session()->put('user', ['fullName' => 'Import CMS 3.0', 'userName' => 'system']);

/* ---------------------------------------------------------------- helpers */

function cms3_clean($s): string
{
    return trim(preg_replace('/[\s\x{200B}]+/u', ' ', (string) $s));
}

/** 'NA', '-', '' ... => null */
function cms3_nullish($s): ?string
{
    $v = cms3_clean($s);
    return in_array(mb_strtoupper($v), ['', 'NA', 'N/A', '-'], true) ? null : $v;
}

function cms3_cas_list($s): array
{
    preg_match_all('/\b\d{2,7}-\d{2}-\d\b/', (string) $s, $m);
    return array_values(array_unique($m[0]));
}

/** Dữ liệu thử trong Access (OQ1, OQ02, Loại OQLHC.., ĐVT OQDV.., định khu OQDK..) */
function cms3_is_test_row(array $r): bool
{
    return (bool) preg_match('/^OQ\d*$/i', cms3_clean($r['TenhoachatTA'] ?? ''))
        || (bool) preg_match('/^OQ/i', cms3_clean($r['Loai'] ?? ''))
        || (bool) preg_match('/^OQDV/i', cms3_clean($r['DVT'] ?? ''))
        || (bool) preg_match('/^OQDK/i', cms3_clean($r['Dinhkhu'] ?? ''));
}

/**
 * Khử trùng dòng query Access. Access có mã dùng TRÙNG MHC (H0417 & H0783 cùng H04170)
 * => khoá phải là Mahoachat + MHC, không phải chỉ MHC.
 */
function cms3_unique_rows(array $rows): array
{
    $u = [];
    foreach ($rows as $r) {
        $u[$r['Mahoachat'] . '|' . $r['MHC']] = $r;
    }
    $u = array_values($u);
    usort($u, fn ($a, $b) => strcmp($a['Mahoachat'], $b['Mahoachat']));
    return $u;
}

function cms3_access_groups(array $r): array
{
    $g = [];
    foreach (CMS3['pl_flags'] as $f => $n) {
        if (! empty($r[$f])) {
            $g[] = $n;
        }
    }
    return $g;
}

/** Cảnh báo an toàn từ 5 cờ Access. AnMon: bazơ nếu tên có hydroxide/amine..., còn lại axit (heuristic - cần rà). */
function cms3_safety_warnings(array $r): array
{
    $codes = [];
    $liquid = in_array(mb_strtolower(trim((string) ($r['DVT'] ?? ''))), ['ml', 'l'], true);
    $name = cms3_clean($r['TenhoachatTA'] ?? '');
    if (! empty($r['DeChay'])) {
        $codes[] = $liquid ? 'FLAMMABLE_LIQUID' : 'FLAMMABLE_SOLID';
    }
    if (! empty($r['AnMon'])) {
        $codes[] = preg_match('/hydroxide|ammonia|amine\b|hydrazine|hydroxyde/i', $name) ? 'CORROSIVE_BASE' : 'CORROSIVE_ACID';
    }
    if (! empty($r['Oxyhoa'])) {
        $codes[] = 'OXIDIZING';
    }
    if (! empty($r['Doc'])) {
        $codes[] = 'TOXIC';
    }
    if (! empty($r['DeNo'])) {
        $codes[] = 'EXPLOSIVE';
    }
    return array_values(array_intersect(array_keys(config('chemical.safety_warnings')), $codes));
}

function cms3_json(string $file): array
{
    $data = json_decode(file_get_contents($file), true);
    if (! is_array($data)) {
        throw new RuntimeException("Không đọc được JSON: $file");
    }
    // ConvertTo-Json của PowerShell trả object khi chỉ có 1 dòng
    return array_is_list($data) ? $data : [$data];
}

function cms3_write_csv(string $file, array $header, array $rows): void
{
    $fh = fopen($file, 'w');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, $header);
    foreach ($rows as $row) {
        fputcsv($fh, $row);
    }
    fclose($fh);
}

/** Mã Dinhkhu -> [tủ, tầng, vị trí] hoặc null (không đặt định khu). */
function cms3_parse_location(string $raw): ?array
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

/**
 * Như cms3_parse_location, thêm các dạng mã của QC NM1 / PTPT (7_add_departments.php):
 *   T1-A1, T088.1-D2 -> Tủ T1 / Tầng A / Vị trí 1;  TL1-10, T25V-3, T088.2-1 -> Tủ (phần trước "-") / Tầng 1 / Vị trí n;
 *   TLA1 -> Tủ TL / Tầng A / Vị trí 1.
 * Tách riêng để không đổi định khu QC1/QC2 nếu chạy lại bước 2.
 */
function cms3_parse_location_ext(string $raw): ?array
{
    $d = mb_strtoupper(cms3_clean($raw));
    if (preg_match('/^([A-Z]*\d+(?:\.\d+)?)-([A-Z]+)(\d+)$/', $d, $m)) {
        return [$m[1], $m[2], $m[3]];
    }
    if (preg_match('/^(\S+)-(\d+)$/u', $d, $m)) {
        return [$m[1], '1', $m[2]];
    }
    if (preg_match('/^(TL)([A-Z])(\d+)$/', $d, $m)) {
        return [$m[1], $m[2], $m[3]];
    }
    return cms3_parse_location($raw);
}

function cms3_tier_position(string $t): int
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

/**
 * Mã Access (H…) => id danh mục hoá chất công ty, đọc lịch sử "Thêm mới" theo thứ tự id: mã bị tách ở bước 6
 * có 2 dòng "Thêm mới" (danh mục cũ + danh mục mới) - dòng sau (danh mục mới) thắng.
 */
function cms3_category_of_access_code(): array
{
    $out = [];
    // + dòng "Cập nhật" của bước 8 (mã tạo trên CMS 3.0 sau lần nạp, gộp vào danh mục sẵn có)
    $rows = Illuminate\Support\Facades\DB::table('chemical_category_histories')->whereIn('action', ['Thêm mới', 'Cập nhật'])
        ->orderBy('id')->get(['chemical_category_id', 'action', 'change_note']);
    foreach ($rows as $h) {
        $note = (string) $h->change_note;
        if (! str_contains($note, 'CMS 3.0') || ($h->action === 'Cập nhật' && ! str_contains($note, 'CMS 3.0, gộp thêm mã'))) {
            continue;
        }
        preg_match_all('/\bH\d{4,5}\b/', (string) $h->change_note, $m);
        foreach ($m[0] as $code) {
            $out[$code] = (int) $h->chemical_category_id;
        }
    }
    return $out;
}

/** Mã Access => id bản ghi, đọc từ ghi chú lịch sử "Thêm mới" (… mã H0001, H0002 …). */
function cms3_codes_from_history(string $table, string $idColumn, string $noteColumn = 'change_note', array $where = []): array
{
    $q = Illuminate\Support\Facades\DB::table($table)->where('action', 'Thêm mới');
    foreach ($where as $col => $val) {
        $q->where($col, $val);
    }
    $out = [];
    foreach ($q->get([$idColumn, $noteColumn]) as $h) {
        if (! str_contains((string) $h->{$noteColumn}, 'CMS 3.0')) {
            continue;
        }
        preg_match_all('/\bH\d{4,5}\b/', (string) $h->{$noteColumn}, $m);
        foreach ($m[0] as $code) {
            $out[$code] = (int) $h->{$idColumn};
        }
    }
    return $out;
}
