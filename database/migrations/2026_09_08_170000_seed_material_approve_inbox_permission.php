<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền "is_BOD" - HỘP KÝ DUYỆT LIÊN PHÒNG BAN của màn Sử Dụng Vật Tư.
 *
 * Người ký thuộc phòng ban chung (deparments.is_general = 0: Ban Giám Đốc, Cung Ứng,
 * Đăng Ký Thuốc...) khi có nhiều phiếu đề nghị cấp phát vật tư của các phòng khác nhau
 * chờ mình ký thì phải chuyển bộ phận từng lần rất mất thời gian. Quyền này bật một tab
 * riêng gom hết các phiếu ĐANG CHỜ CHÍNH NGƯỜI ĐANG ĐĂNG NHẬP KÝ, mọi phòng ban trong
 * cùng công ty, ký / từ chối tại chỗ không cần chuyển bộ phận.
 *
 * Tab chỉ hiện khi: có quyền is_BOD  VÀ  phòng ban nhà của user là phòng ban chung
 * (deparments.is_general = 0). Theo mẫu 2026_09_06_160100_seed_material_transfer_permissions.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'is_BOD', 'Ban Giám Đốc - Hộp Ký Duyệt Liên Phòng Ban', 'Thấy tab "Ký duyệt (mọi phòng ban)" ở màn Sử Dụng Vật Tư và các màn Dự Trù (Hoá Chất / Vật Tư / Chất Chuẩn): gom các phiếu của mọi phòng ban đang chờ chính mình ký, ký / từ chối tại chỗ không cần chuyển bộ phận'],
    ];

    /** Nhóm quyền được cấp sẵn toàn bộ quyền khi cài đặt */
    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission[1]], [
                'permission_group' => $permission[0],
                'display_name' => $permission[2],
                'description' => $permission[3],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
