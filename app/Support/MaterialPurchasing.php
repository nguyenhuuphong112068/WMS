<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * BỘ PHẬN MUA HÀNG của vật tư dự trù -> phòng ban thật trong bảng deparments.
 *
 * Danh mục vật tư khai material_categories.purchasing_department là một KHOÁ (supply / admin -
 * xem MaterialClassification::PURCHASING_DEPARTMENTS), không phải id phòng ban. Phòng ban nào
 * đảm nhận khoá nào khai ở Dữ Liệu Gốc > Phòng Ban (cột deparments.purchasing_key). Từ 14/11/2026
 * mỗi nhà máy chạy một bản WMS riêng (DB riêng) và TỰ CÓ bộ phận mua hàng: NM2 = Cung Ứng + Hành
 * Chính Nhân Sự, NM1 = Cung Ứng - NM1 + Hành Chính - NM1 (migration 2026_11_14_091000). Trong một DB
 * bộ phận mua hàng tiếp nhận mọi dự trù / đề nghị VPP. Vật tư ngoài danh mục (không có khoá)
 * mặc định do Cung Ứng mua.
 */
class MaterialPurchasing
{
    public const DEFAULT_KEY = 'supply';

    /** @var array<int, ?string> purchasing_key theo id phòng - nạp một lần cho cả request */
    private static ?array $keys = null;

    public static function keyOf(?string $key): string
    {
        return $key && isset(MaterialClassification::PURCHASING_DEPARTMENTS[$key]) ? $key : self::DEFAULT_KEY;
    }

    private static function departmentKeys(): array
    {
        return self::$keys ??= DB::table('deparments')
            ->where('isActive', 1)
            ->whereNotNull('purchasing_key')
            ->pluck('purchasing_key', 'id')
            ->mapWithKeys(fn ($key, $id) => [(int) $id => (string) $key])
            ->all();
    }

    /** Các khoá bộ phận mua hàng mà phòng ban này đảm nhận (0 hoặc 1 khoá). */
    public static function keysOfDepartment(int $departmentId): array
    {
        $key = self::departmentKeys()[$departmentId] ?? null;

        return $key ? [$key] : [];
    }

    /** Mọi phòng ban đang hoạt động đảm nhận khoá $key, theo id tăng dần. */
    public static function departmentIdsOf(string $key): array
    {
        $ids = array_keys(array_filter(self::departmentKeys(), fn ($value) => $value === $key));
        sort($ids);

        return $ids;
    }

    /**
     * Phòng ban đang chọn có phải bộ phận mua hàng của khoá $key không. Bộ phận mua hàng phục vụ
     * mọi công ty nên không còn đối chiếu công ty của phiếu ($companyId giữ để không đổi chữ ký hàm).
     */
    public static function isPurchaser(int $departmentId, ?string $key, ?int $companyId = null): bool
    {
        return in_array(self::keyOf($key), self::keysOfDepartment($departmentId), true);
    }
}
