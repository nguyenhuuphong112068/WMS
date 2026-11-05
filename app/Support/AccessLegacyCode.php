<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TẠM THỜI - mã cũ bên Access (CMS 3.0 / SMS 2.0) của từng mã danh mục công ty, chỉ để đối chiếu
 * dữ liệu chuyển đổi. Không có cột riêng: đọc lại từ ghi chú lịch sử "Thêm mới" mà script nạp
 * (.claude/skills/cms3-access-import, sms2-access-import) đã ghi:
 *   - chemical_category_histories: "Nạp từ CMS 3.0, mã cũ: H0483, H0484."; mã nào sau đó tách sang mã danh
 *     mục khác (đổi tên theo TenhoachatTA 04/11/2026) thì có thêm dòng "Cập nhật" "Tách mã CMS 3.0 sang C01013: H0484."
 *   - standard_category_histories: "Nạp từ SMS 2.0, mã Access: S3760 (ĐVT mg; ...), S3786 (...)."
 *   - mã tạo trên Access sau lần nạp đầu, gộp vào dòng sẵn có (04/11/2026): dòng "Cập nhật"
 *     "CMS 3.0, gộp thêm mã: H1085 ..." / "Nạp từ SMS 2.0, gộp thêm mã Access: S4160 (...)."
 *   - mã lô / ống: ghi chú phiếu nhập tồn đầu kỳ - "... CMS 3.0 - mã XN 4000191, ..." / "...; mã ống SMS 2.0 QC1CTC26010039.2"
 *     (ống chuẩn WMS giữ nguyên mã Access, chỉ khác khi 2 phòng trùng mã -> thêm hậu tố bộ phận).
 *
 * Đối chiếu xong thì gỡ: xoá file này + mọi chỗ gọi AccessLegacyCode (grep): cột "Mã ... Access" ở 2 bảng Danh Mục
 * công ty, dòng phụ "Access" ở 2 tab danh mục của phòng và 2 màn Tồn Kho (HC: trường access của JSON).
 */
class AccessLegacyCode
{
    /** [chemical_category_id => ['H0001', ...]] */
    public static function byChemicalCategory(): array
    {
        $codes = [];

        $rows = DB::table('chemical_category_histories')
            ->where(function ($query) {
                $query->where('action', 'Thêm mới')->where('change_note', 'like', 'Nạp từ CMS 3.0%')
                    ->orWhere('change_note', 'like', 'Tách mã CMS 3.0%')
                    ->orWhere('change_note', 'like', 'CMS 3.0, gộp thêm mã%');
            })
            ->orderBy('id')
            ->get(['chemical_category_id', 'change_note']);

        foreach ($rows as $row) {
            preg_match_all('/\bH\d{4,5}\b/', (string) $row->change_note, $m);
            $id = $row->chemical_category_id;

            $codes[$id] = str_starts_with((string) $row->change_note, 'Tách mã')
                ? array_values(array_diff($codes[$id] ?? [], $m[0]))
                : array_values(array_unique(array_merge($codes[$id] ?? [], $m[0])));
        }

        return array_filter($codes);
    }

    /** [standard_category_id => ['S0001', ...]] */
    public static function byStandardCategory(): array
    {
        return self::collect('standard_category_histories', 'standard_category_id', 'Nạp từ SMS 2.0%',
            function (string $note) {
                // Mỗi mã đứng ngay sau ": " hoặc "), " và trước " (" phần chi tiết
                preg_match_all('/(?:: |\), )([^\s(,]+) \(/u', $note, $m);

                return $m[1];
            });
    }

    /** Mã xuất nhập (lô) bên CMS 3.0 của một phiếu nhập hoá chất tồn đầu kỳ, đọc từ ghi chú phiếu. */
    public static function chemicalLot(?string $note): ?string
    {
        return preg_match('/CMS 3\.0 - mã XN ([^,\s]+)/u', (string) $note, $m) ? $m[1] : null;
    }

    /** Mã ống bên SMS 2.0 khi khác mã ống WMS (2 phòng trùng mã -> WMS thêm hậu tố bộ phận). */
    public static function standardTube(?string $note): ?string
    {
        return preg_match('/mã ống SMS 2\.0 ([^;\s]+)/u', (string) $note, $m) ? $m[1] : null;
    }

    private static function collect(string $table, string $column, string $prefix, callable $parse): array
    {
        $codes = [];

        $rows = DB::table($table)
            ->whereIn('action', ['Thêm mới', 'Cập nhật'])
            ->where('change_note', 'like', $prefix)
            ->orderBy('id')
            ->get([$column, 'change_note']);

        foreach ($rows as $row) {
            $codes[$row->{$column}] = array_values(array_unique(array_merge(
                $codes[$row->{$column}] ?? [],
                $parse((string) $row->change_note)
            )));
        }

        return $codes;
    }
}
