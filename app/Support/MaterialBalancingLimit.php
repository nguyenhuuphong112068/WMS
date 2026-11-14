<?php

namespace App\Support;

/**
 * HẠN MỨC CÂN ĐỐI VẬT TƯ - theo tiêu chí "Mức Độ Quan Trọng" của danh mục công ty
 * (material_categories.classification, khoá importance):
 *
 *   - Quan trọng = Cao  : KHÔNG cho cân đối (hạn mức 0).
 *   - Quan trọng = Thấp : tổng các lần cân đối của một mã xuất nhập <= 2% lượng nhập đầu.
 *   - Chưa phân loại    : áp như Thấp (2%).
 *
 * Dùng chung cho màn hình Cân Đối (MaterialInventoryController) và chốt Kiểm Kê định kỳ
 * (MaterialStocktakeController) để hai nơi không lệch luật nhau.
 * VPP KHÔNG dùng lớp này - VPP cân đối tự do, không hạn mức.
 */
class MaterialBalancingLimit
{
    /** Tỷ lệ tối đa cho vật tư mức độ quan trọng Thấp. */
    public const LOW_IMPORTANCE_RATIO = 0.02;

    public static function isHighImportance(?string $classification): bool
    {
        return (MaterialClassification::decode($classification)['importance'] ?? null) === 'high';
    }

    public static function ratio(?string $classification): float
    {
        return self::isHighImportance($classification) ? 0.0 : self::LOW_IMPORTANCE_RATIO;
    }

    public static function percent(): int
    {
        return (int) round(self::LOW_IMPORTANCE_RATIO * 100);
    }

    /** Hạn mức (±) tính trên lượng nhập đầu của mã xuất nhập. */
    public static function limit(float $imported, ?string $classification): float
    {
        return round(abs($imported) * self::ratio($classification), 4);
    }
}
