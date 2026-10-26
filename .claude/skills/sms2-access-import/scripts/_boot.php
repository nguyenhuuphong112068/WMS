<?php
/**
 * Khởi động Laravel + cấu hình + hàm dùng chung cho script nạp chất chuẩn SMS 2.0 (Access).
 *
 * Sửa SMS2 ở đây khi đổi ánh xạ nhóm chuẩn / điều kiện bảo quản / đơn vị - KHÔNG sửa trong script.
 */

$root = dirname(__DIR__, 4); // .claude/skills/sms2-access-import/scripts -> gốc dự án
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const SMS2 = [
    // Người ghi created_by/approved_by. Dùng để nhận ra (và xoá khi chạy lại) dữ liệu do script tạo.
    'actor' => 'Import SMS 2.0 (system)',

    // Bophan Access => [id phòng WMS, mã + tên kho chất chuẩn dựng theo định khu Access, bảng định khu Access].
    // Giống hoá chất CMS 3.0: QC1 -> id 20 (QC-B1), QC2 -> id 9 (QC-B2). QC (DinhKhu3), AD (DinhKhu4): hỏi người dùng.
    'departments' => [
        'QC1' => ['id' => 20, 'warehouse_code' => 'QC1-CC', 'warehouse_name' => 'Kho Chất Chuẩn QC1', 'zone_table' => 'dbo_T_DinhKhu'],
        'QC2' => ['id' => 9, 'warehouse_code' => 'QC2-CC', 'warehouse_name' => 'Kho Chất Chuẩn QC2', 'zone_table' => 'dbo_T_DinhKhu2'],
    ],

    // Cờ nhóm Access (dbo_T_LoaiChuan) => khoá config('standard.groups')
    'group_flags' => ['API' => 'PRS', 'IMPU' => 'IMPRS', 'CTC' => 'CTC', 'VKN' => 'VKN',
                      'CN' => 'CN', 'CHH' => 'CHC', 'CNL' => 'CNL'],

    /*
     | Điều kiện bảo quản: người dùng chọn CHUẨN HOÁ về ít nhóm (26/10/2026).
     | Khoá = tên storage_conditions (có sẵn thì dùng lại, chưa có thì tạo mới).
     | Giá trị = chuỗi BaoQuan Access đã chuẩn hoá bằng sms2_storage_key() (viết thường, bỏ khoảng trắng).
     | Chuỗi gốc khác tên nhóm được ghi vào note của danh mục để không mất chi tiết (±3 °C, -25 to -10 °C...).
     | NA / '-' => để trống. Chuỗi chưa khai ở đây => để trống + báo cáo.
     */
    'storage_map' => [
        'Tủ lạnh (2-8°C)' => ['2-8oc', '+5oc±3oc', 'refrigerator', '4oc', '4oc±4oc', '3-8oc', 'cool', '0°cto10°c'],
        'Tủ đông (-20°C)' => ['-20oc±5oc', '-20oc', 'freezer', '-25octo-10°c', '-15°cto-25°c', '-18oc', '-18oc±4oc',
                              '<-15oc', '-25', '-25÷-15°c'],
        'Nhiệt độ phòng (15-25°C)' => ['roomtemperature', 'nhiệtđộphòng', '20oc±4oc', '15-25oc', '15°cto25°c', '10-25oc',
                                       '25oc', '20oc', 'ambienttemperature', '20oc±5oc', '20-25oc'],
        'Nhiệt độ 2-30°C' => ['2-30oc', '15-30oc'],
        'Tránh ánh sáng' => ['protectfromlight', 'tránhsáng'],
        'Bảo quản kín' => ['tronglọkín'],
        'Bảo quản kín, tránh ánh sáng' => ['tronglọkín,tránhsáng', 'tronglọkín,tránhánhsáng'],
        'Nơi khô ráo' => ['dryingconditions'],
    ],

    // ĐVT Access chưa có trong units (khoá = DVT viết thường) => [short_name, name] tạo mới nhóm count;
    // giá trị chuỗi = dùng lại đơn vị có sẵn mang short_name đó.
    'units' => ['lo' => 'lọ', 'pill' => 'viên', 'vial' => ['vial', 'Lọ nhỏ (vial)'], 'cell' => ['cell', 'Cell'],
                'miếng' => ['miếng', 'Miếng'], 'phổ' => ['Phổ', 'Phổ chuẩn']],
];

session()->put('user', ['fullName' => 'Import SMS 2.0', 'userName' => 'system']);

/* ---------------------------------------------------------------- helpers */

function sms2_clean($s): string
{
    return trim(preg_replace('/[\s\x{200B}\x{00A0}]+/u', ' ', (string) $s));
}

/** 'NA', '-', '' ... => null */
function sms2_nullish($s): ?string
{
    $v = sms2_clean($s);
    return in_array(mb_strtoupper($v), ['', 'NA', 'N/A', '-'], true) ? null : $v;
}

function sms2_storage_key($s): string
{
    return mb_strtolower(preg_replace('/\s+/u', '', sms2_clean($s)));
}

function sms2_json(string $file): array
{
    $data = json_decode(file_get_contents($file), true);
    if (! is_array($data)) {
        throw new RuntimeException("Không đọc được JSON: $file");
    }
    return array_is_list($data) ? $data : [$data];
}

function sms2_write_csv(string $file, array $header, array $rows): void
{
    $fh = fopen($file, 'w');
    fwrite($fh, "\xEF\xBB\xBF");
    fputcsv($fh, $header);
    foreach ($rows as $row) {
        fputcsv($fh, $row);
    }
    fclose($fh);
}

/**
 * Khử trùng dòng Q_DMHC_ChungHH. Query join theo MHC nên mã dùng TRÙNG MHC (S3785 & S3798 cùng S37980)
 * bị nhân dòng, và Machuan có thể ra 2 phiên bản (S2705 v2 + v3) => mỗi Machuan giữ Ver lớn nhất.
 */
function sms2_unique_rows(array $rows): array
{
    $u = [];
    foreach ($rows as $r) {
        $k = $r['Machuan'];
        if (! isset($u[$k]) || (int) $r['Ver'] > (int) $u[$k]['Ver']) {
            $u[$k] = $r;
        }
    }
    uasort($u, fn ($a, $b) => strnatcmp($a['Machuan'], $b['Machuan']));
    return array_values($u);
}

/** Giá trị xuất hiện nhiều nhất (bỏ null/''); bằng nhau thì lấy giá trị gặp trước (mã Access nhỏ hơn). */
function sms2_mode(array $values)
{
    $values = array_values(array_filter($values, fn ($v) => $v !== null && $v !== ''));
    if (! $values) {
        return null;
    }
    $c = array_count_values(array_map('strval', $values));
    $best = max($c);
    foreach ($values as $v) {
        if ($c[(string) $v] === $best) {
            return $v;
        }
    }
}

/** ĐVT Access => units.id (theo SMS2['units'] cho ĐVT viết khác), null nếu chưa có. */
function sms2_unit_id($dvt): ?int
{
    static $cache = [];
    $k = mb_strtolower(sms2_clean($dvt));
    if ($k === '') {
        return null;
    }
    if (! array_key_exists($k, $cache)) {
        $def = SMS2['units'][$k] ?? null;
        $short = is_array($def) ? $def[0] : ($def ?? $k);
        $id = Illuminate\Support\Facades\DB::table('units')->where('short_name', $short)->value('id');
        if (! $id) {
            return null; // chưa có thì không nhớ - bước 1 có thể vừa tạo đơn vị mới
        }
        $cache[$k] = (int) $id;
    }
    return $cache[$k];
}

/**
 * Nhóm ĐVT dùng trong khoá gộp danh mục công ty (người dùng chốt 26/10/2026): khối lượng (mg/g/µg)
 * gộp chung, thể tích (ml/µl) gộp chung vì quy đổi được; mỗi đơn vị đếm (lọ, Phổ, ống...) tách riêng.
 */
function sms2_unit_key(?int $unitId): string
{
    if (! $unitId) {
        return 'none';
    }
    $group = Illuminate\Support\Facades\DB::table('units')->where('id', $unitId)->value('unit_group');
    return in_array($group, ['mass', 'volume'], true) ? $group : 'u' . $unitId;
}

/**
 * Xoá tồn đầu kỳ do bộ script tạo (bước 3): phiếu sử dụng "tổng xuất SMS 2.0" + phiếu nhập, kèm lịch sử.
 * Dừng nếu đã có phiếu nhập / sử dụng do người dùng tạo (dữ liệu thật) - trừ khi --force.
 * Gọi ở đầu bước 1, 2, 3 vì tồn trỏ vào danh mục công ty, danh mục phòng và định khu.
 */
function sms2_delete_stock(array $argv, callable $inc): void
{
    $DB = Illuminate\Support\Facades\DB::class;
    $actor = SMS2['actor'];
    $real = $DB::table('standard_exports')->where('created_by', '<>', $actor)->exists()
        || $DB::table('standard_imports')->where('created_by', '<>', $actor)->exists();
    if ($real && ! in_array('--force', $argv, true)) {
        throw new RuntimeException('Đã có phiếu nhập / sử dụng chất chuẩn do người dùng tạo - không xoá tồn đầu kỳ. Thêm --force nếu chắc chắn.');
    }
    $imp = $DB::table('standard_imports')->where('created_by', $actor)->pluck('id');
    // Phiếu đánh giá hạn dùng (bước 4) trỏ vào ống của tồn đầu kỳ -> xoá trước, chạy lại bước 4 sau
    sms2_delete_stability($argv, $inc, $imp->all());
    $exp = $DB::table('standard_exports')->where('created_by', $actor)->pluck('id');
    $DB::table('standard_export_histories')->whereIn('standard_export_id', $exp)->delete();
    $inc('cleanup_exports', $DB::table('standard_exports')->whereIn('id', $exp)->delete());
    $DB::table('standard_import_histories')->whereIn('standard_import_id', $imp)->delete();
    $inc('cleanup_imports', $DB::table('standard_imports')->whereIn('id', $imp)->delete());
}

/**
 * Xoá phiếu đánh giá hạn dùng do bước 4 tạo (created_by = actor) kèm mốc + nhật ký; $importIds khác null thì
 * xoá thêm mọi phiếu trỏ vào các ống đó (bước 3 sắp xoá ống). Dừng nếu phiếu người dùng tự lập / mốc người dùng
 * đã ghi thêm sau khi nạp (updated_by khác actor) - trừ khi --force.
 */
function sms2_delete_stability(array $argv, callable $inc, ?array $importIds = null): void
{
    $DB = Illuminate\Support\Facades\DB::class;
    if (! Illuminate\Support\Facades\Schema::hasTable('standard_stability_assessment_list')) {
        return;
    }
    $actor = SMS2['actor'];
    $q = $DB::table('standard_stability_assessment_list')->where(function ($q) use ($actor, $importIds) {
        $q->where('created_by', $actor);
        if ($importIds) {
            $q->orWhereIn('import_id', $importIds);
        }
    });
    $lists = $q->get(['id', 'created_by']);
    $ids = $lists->pluck('id');
    $touched = $lists->contains(fn ($l) => $l->created_by !== $actor)
        || $DB::table('standard_stability_assessment_histories')->whereIn('standard_stability_assessment_list_id', $ids)
            ->where('created_by', '<>', $actor)->exists();
    if ($touched && ! in_array('--force', $argv, true)) {
        throw new RuntimeException('Đã có phiếu / thao tác đánh giá hạn dùng do người dùng ghi trên ống nạp từ SMS 2.0 - không xoá. Thêm --force nếu chắc chắn.');
    }
    $DB::table('standard_stability_assessment_histories')->whereIn('standard_stability_assessment_list_id', $ids)->delete();
    $inc('cleanup_stability_items', $DB::table('standard_stability_assessment_item')->whereIn('standard_stability_assessment_list_id', $ids)->delete());
    $inc('cleanup_stability_lists', $DB::table('standard_stability_assessment_list')->whereIn('id', $ids)->delete());
}

/** Mã Access => id bản ghi, đọc từ ghi chú lịch sử "Thêm mới" (… mã S0375, S0376 …). */
function sms2_codes_from_history(string $table, string $idColumn, string $noteColumn = 'change_note', array $where = []): array
{
    $q = Illuminate\Support\Facades\DB::table($table)->where('action', 'Thêm mới');
    foreach ($where as $col => $val) {
        $q->where($col, $val);
    }
    $out = [];
    foreach ($q->get([$idColumn, $noteColumn]) as $h) {
        if (! str_contains((string) $h->{$noteColumn}, 'SMS 2.0')) {
            continue;
        }
        preg_match_all('/\bS\d{4}\b/', (string) $h->{$noteColumn}, $m);
        foreach ($m[0] as $code) {
            $out[$code] = (int) $h->{$idColumn};
        }
    }
    return $out;
}
