<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * TRẢ HOÁ CHẤT ACETONITRIL / METHANOL VỀ KHO (tab "return" của Sử Dụng Hoá Chất).
 *
 * Dung môi pha động dùng không hết được khai trả lại đúng lô đã lấy ra. Lượng trả lưu
 * thành MỘT DÒNG chemical_exports:
 *   type = 'return', amount ÂM (-lượng trả), return_of_id = phiếu sử dụng gốc.
 *
 * Nhờ số âm, mọi chỗ tính tồn "nhập + cân đối - SUM(chemical_exports.amount)" tự cộng lại
 * lượng trả mà không phải sửa từng truy vấn; thống kê "đã sử dụng" cũng ra số thực dùng.
 * Khoá dòng trả (status_id = 0) = huỷ phiếu trả, tồn bị trừ lại.
 *
 * Quy tắc: tổng lượng trả (còn hiệu lực) của một phiếu sử dụng <= số lượng của phiếu đó.
 */
class ChemicalReturn
{
    /** Loại dòng chemical_exports của phiếu trả về kho. */
    public const TYPE = 'return';

    /** Quyền khai báo / huỷ phiếu trả hoá chất về kho. */
    public const PERMISSION = 'export_chemical_return';

    /** Tên hoá chất (chem_names.name, so khớp nguyên tên, không phân biệt hoa thường) được trả về kho. */
    public const NAMES = ['acetonitril', 'acetonitrile', 'methanol'];

    /** Sai số so sánh số lượng (cột decimal 15,4). */
    public const EPSILON = 0.00005;

    private static ?array $categoryIdsCache = null;

    /** Mã danh mục hoá chất công ty (chemical_categories.id) là Acetonitril hoặc Methanol. */
    public static function categoryIds(): array
    {
        if (self::$categoryIdsCache !== null) {
            return self::$categoryIdsCache;
        }

        $placeholders = implode(',', array_fill(0, count(self::NAMES), '?'));

        return self::$categoryIdsCache = DB::table('chemical_categories')
            ->join('chem_names', 'chemical_categories.chem_names_id', '=', 'chem_names.id')
            ->whereRaw('LOWER(TRIM(chem_names.name)) IN ('.$placeholders.')', self::NAMES)
            ->pluck('chemical_categories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function isReturnableCategory($categoryId): bool
    {
        return in_array((int) $categoryId, self::categoryIds(), true);
    }

    /**
     * Tổng lượng ĐÃ TRẢ (số dương) còn hiệu lực theo từng phiếu sử dụng gốc: [export_id => lượng].
     *
     * @param  int[]  $exportIds
     */
    public static function returnedByExport(array $exportIds, ?int $ignoreReturnId = null)
    {
        if (! $exportIds) {
            return collect();
        }

        return DB::table('chemical_exports')
            ->select('return_of_id', DB::raw('-SUM(amount) as total'))
            ->whereIn('return_of_id', $exportIds)
            ->where('type', self::TYPE)
            ->where('status_id', 1)
            ->when($ignoreReturnId, fn ($q) => $q->where('id', '<>', $ignoreReturnId))
            ->groupBy('return_of_id')
            ->pluck('total', 'return_of_id')
            ->map(fn ($v) => (float) $v);
    }

    public static function returnedOf(int $exportId): float
    {
        return (float) (self::returnedByExport([$exportId])[$exportId] ?? 0);
    }
}
