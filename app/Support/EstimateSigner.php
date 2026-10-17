<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * NGƯỜI KÝ PHIẾU DỰ TRÙ THEO VAI TRÒ + PHẠM VI PHÒNG BAN.
 *
 * Quy trình cố định 2 bước, cả hai đều KHÔNG chỉ định đích danh (bảng *_estimate_signs ghi
 * user_id NULL + role_names):
 *   - Bước 1: vai trò config('estimate.department_manager_roles') - Quản Lý Bộ Phận.
 *   - Bước 2: vai trò config('estimate.bod_roles') - Ban Giám Đốc.
 * Mọi user đang hoạt động thuộc vai trò của bước VỚI PHẠM VI BAO GỒM PHÒNG LẬP PHIẾU đều ký
 * được. Một user được tính là có vai trò ở phòng khi:
 *   - phòng chính là phòng đó (user_management.deparment_id) và vai trò CHÍNH là vai trò đó, hoặc
 *   - được gán vai trò đó qua user_role cho đúng phòng đó, hoặc cho mọi phòng (department_id NULL)
 *     - chính là "Phạm vi phòng ban" của từng quyền ở màn Phân quyền.
 *
 * Khác user_has_any_role(): hàm đó xét vai trò chính bất kể phòng nào - ở đây bắt buộc đúng
 * phòng. Không mặc nhiên cho Admin ký thay (chữ ký điện tử phải đúng người có thẩm quyền).
 *
 * Dùng chung cho trait EstimateSignFlow (trình ký / ký / báo lượt ký) và HomeController (bảng
 * "Chờ bạn ký duyệt"). Query Builder thuần.
 */
class EstimateSigner
{
    /** @var array<string, \Illuminate\Support\Collection> */
    private static array $cache = [];

    /** Các vai trò quản lý bộ phận (bước 1). */
    public static function managerRoles(): array
    {
        return (array) config('estimate.department_manager_roles', []);
    }

    /** Các vai trò Ban Giám Đốc (bước 2). */
    public static function bodRoles(): array
    {
        return (array) config('estimate.bod_roles', []);
    }

    /** Vai trò ghi trên một dòng bước ký (cột role_names dạng "A, B"). */
    public static function stepRoles($sign): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($sign->role_names ?? '')))));
    }

    /**
     * Bước ký theo vai trò CÓ PHẠM VI PHÒNG BAN (Quản Lý Bộ Phận / Ban Giám Đốc): không đích danh,
     * role_names thuộc một trong hai nhóm vai trò trên. Phiếu luồng cũ ghi vai trò khác (Trưởng
     * Phòng...) thì không phải - nơi gọi tự xét theo user_has_any_role như trước.
     * Nhận dòng bảng *_estimate_signs (user_id) lẫn dòng đã đổi tên cột (signer_id - HomeController).
     */
    public static function isScopedStep($sign): bool
    {
        if (! $sign || ($sign->user_id ?? $sign->signer_id ?? null)) {
            return false;
        }

        return (bool) array_intersect(self::stepRoles($sign), array_merge(self::managerRoles(), self::bodRoles()));
    }

    /** Người ký được bước $sign của phiếu thuộc phòng $departmentId. */
    public static function signersOf($sign, int $departmentId)
    {
        return self::usersWithRoles(self::stepRoles($sign), $departmentId);
    }

    public static function canSign(int $userId, $sign, int $departmentId): bool
    {
        return $userId > 0 && self::signersOf($sign, $departmentId)->contains('id', $userId);
    }

    /** Quản lý bộ phận của một phòng (bước 1). */
    public static function managers(int $departmentId)
    {
        return self::usersWithRoles(self::managerRoles(), $departmentId);
    }

    /** Ban Giám Đốc có phạm vi trên một phòng (bước 2). */
    public static function bods(int $departmentId)
    {
        return self::usersWithRoles(self::bodRoles(), $departmentId);
    }

    /**
     * User đang hoạt động thuộc một trong các vai trò $roleNames với phạm vi bao gồm phòng
     * $departmentId: id, fullName, userName.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function usersWithRoles(array $roleNames, int $departmentId)
    {
        sort($roleNames);
        $key = $departmentId.'|'.implode(',', $roleNames);

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $roleIds = $roleNames ? DB::table('roles')->whereIn('name', $roleNames)->pluck('id')->all() : [];

        if (! $departmentId || ! $roleIds) {
            return self::$cache[$key] = collect();
        }

        $assigned = DB::table('user_role')
            ->whereIn('role_id', $roleIds)
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $departmentId))
            ->pluck('user_id')
            ->all();

        return self::$cache[$key] = DB::table('user_management')
            ->where('isActive', 1)
            ->where(function ($query) use ($departmentId, $roleIds, $assigned) {
                $query->where(fn ($sub) => $sub->where('deparment_id', $departmentId)->whereIn('role_id', $roleIds));

                if ($assigned) {
                    $query->orWhereIn('id', $assigned);
                }
            })
            ->orderBy('fullName', 'asc')
            ->get(['id', 'fullName', 'userName']);
    }
}
