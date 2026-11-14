<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Các lần khai báo trên Cổng thông tin quốc gia của Danh Mục Hoá Chất Công Ty
 * (bảng chemical_portal_declarations - mỗi lần khai báo một dòng: Mã hồ sơ + Code nhà sản xuất).
 *
 * Khai báo ở ChemicalCategoryController::declarePortal(), được khai báo NHIỀU LẦN; dòng đã ghi
 * sửa được qua updatePortal() (bắt lý do, ghi Audit Trail). Đọc lại ở:
 *   - cột "Khai Báo Cổng TT Quốc Gia" của tab Danh mục hoá chất công ty (danh sách từng lần)
 *   - modal "Thêm Mặt Hàng Dự Trù" hoá chất (cột Code NSX + badge nhắc khai báo khi chưa có lần nào)
 */
class ChemicalPortalDeclaration
{
    public const TABLE = 'chemical_portal_declarations';

    /**
     * Danh sách khai báo theo mã danh mục, mới nhất trước.
     *
     * @param  array<int>|null  $categoryIds  null = mọi mã
     * @return array<int, array<int, object>>  [chemical_category_id => [{id, declaration_no, manufacturer_code, created_by, created_at, updated_by, updated_at}]]
     */
    public static function byCategory(?array $categoryIds = null): array
    {
        $query = DB::table(self::TABLE)
            ->where('status_id', 1)
            ->select('id', 'chemical_category_id', 'declaration_no', 'manufacturer_code', 'created_by', 'created_at', 'updated_by', 'updated_at')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($categoryIds !== null) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

            if (! $ids) {
                return [];
            }

            $query->whereIn('chemical_category_id', $ids);
        }

        $result = [];

        foreach ($query->get() as $row) {
            $result[(int) $row->chemical_category_id][] = $row;
        }

        return $result;
    }

    /**
     * Gọn cho modal dự trù: [category_id => ['declared' => bool, 'codes' => ['Code NSX', ...]]]
     * - codes: các Code nhà sản xuất khác nhau đã khai báo (bỏ trống), theo thứ tự mới nhất trước.
     * Mã chưa có lần khai báo nào vẫn có mặt với declared = false để giao diện hiện badge nhắc.
     *
     * @param  array<int>  $categoryIds
     */
    public static function summary(array $categoryIds): array
    {
        $declarations = self::byCategory($categoryIds);
        $result = [];

        foreach (array_unique(array_filter(array_map('intval', $categoryIds))) as $id) {
            $rows = $declarations[$id] ?? [];

            $codes = collect($rows)
                ->pluck('manufacturer_code')
                ->map(fn ($code) => trim((string) $code))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $result[$id] = ['declared' => count($rows) > 0, 'codes' => $codes];
        }

        return $result;
    }

    /**
     * Cảnh báo Code nhà sản xuất của một mặt hàng dự trù (null = không cảnh báo). Cùng quy tắc với
     * JS wmsPortalCode (pages/estimate/shared/portalCode.blade.php):
     *   - có code mà code không nằm trong các Code NSX đã khai báo -> "Code ... chưa khai báo Cổng TTQG"
     *   - hoá chất trong danh mục chưa khai báo lần nào, chưa nhập code -> "Hoá chất chưa khai báo Cổng TTQG"
     *
     * @param  array{declared: bool, codes: array<string>}|null  $info  một phần tử của summary()
     */
    public static function warning(?array $info, ?string $code, bool $fromCategory): ?string
    {
        $code = trim((string) $code);
        $codes = $info['codes'] ?? [];

        if ($code !== '') {
            foreach ($codes as $declared) {
                if (mb_strtolower($declared) === mb_strtolower($code)) {
                    return null;
                }
            }

            return 'Code ' . $code . ' chưa khai báo Cổng TTQG';
        }

        if ($fromCategory && ! ($info['declared'] ?? false)) {
            return 'Hoá chất chưa khai báo Cổng TTQG';
        }

        return null;
    }
}
