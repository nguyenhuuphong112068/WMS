<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * BỘ PHẬN MUA HÀNG của vật tư dự trù -> phòng ban thật trong bảng deparments.
 *
 * Danh mục vật tư khai material_categories.purchasing_department là một KHOÁ
 * (supply / admin / it - xem MaterialClassification::PURCHASING_DEPARTMENTS), không phải
 * id phòng ban. Lớp này dò phòng ban tương ứng theo tên / tên viết tắt, trong phạm vi một
 * công ty. Vật tư ngoài danh mục (không có khoá) mặc định do Cung Ứng mua.
 */
class MaterialPurchasing
{
    public const DEFAULT_KEY = 'supply';

    /** Cách nhận ra phòng ban của từng khoá: [tên chứa..., tên viết tắt bắt đầu bằng...]. */
    private const MATCHERS = [
        'supply' => [['Cung Ứng', 'Cung ứng'], ['PROC', 'CU']],
        'admin' => [['Hành Ch'], ['HC']],
        'it' => [['Công Nghệ Thông Tin', 'Công nghệ thông tin'], ['IT']],
    ];

    public static function keyOf(?string $key): string
    {
        return $key && isset(self::MATCHERS[$key]) ? $key : self::DEFAULT_KEY;
    }

    /** Các khoá bộ phận mua hàng mà phòng ban này đảm nhận (thường 0 hoặc 1 khoá). */
    public static function keysOfDepartment(int $departmentId): array
    {
        $department = DB::table('deparments')->where('id', $departmentId)->first();

        if (! $department) {
            return [];
        }

        $keys = [];

        foreach (self::MATCHERS as $key => [$names, $prefixes]) {
            $match = false;

            foreach ($names as $name) {
                $match = $match || mb_stripos((string) $department->name, $name) !== false;
            }

            foreach ($prefixes as $prefix) {
                $match = $match || stripos((string) $department->shortName, $prefix) === 0;
            }

            if ($match) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /** Phòng ban đang chọn có phải bộ phận mua hàng của khoá $key, cùng công ty với phiếu không. */
    public static function isPurchaser(int $departmentId, ?string $key, ?int $companyId): bool
    {
        if (! in_array(self::keyOf($key), self::keysOfDepartment($departmentId), true)) {
            return false;
        }

        $ownCompany = DB::table('deparments')->where('id', $departmentId)->value('company_id');

        return ! $companyId || ! $ownCompany || (int) $ownCompany === (int) $companyId;
    }
}
