<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH THỜI GIAN ĐẶT HÀNG (lead_time_days) ở danh mục công ty của vật tư / hoá chất /
 * chất chuẩn.
 *
 * Thời gian đặt hàng chỉ sửa được bằng nút riêng ở cột "Thời Gian Đặt Hàng" (updateLeadTime của
 * từng controller Danh Mục), không còn nằm trong modal Cập nhật. Không cần quyền Sửa danh mục và
 * không đưa bản ghi về Chờ duyệt - để bộ phận mua hàng tự cập nhật số ngày đặt hàng thực tế.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [2, 'category_material_lead_time', 'Điều Chỉnh Thời Gian Đặt Hàng Vật Tư', 'Sửa số ngày đặt hàng của mã vật tư trong danh mục công ty'],
        [2, 'category_chemical_lead_time', 'Điều Chỉnh Thời Gian Đặt Hàng Hoá Chất', 'Sửa số ngày đặt hàng của mã hoá chất trong danh mục công ty'],
        [2, 'category_standard_lead_time', 'Điều Chỉnh Thời Gian Đặt Hàng Chất Chuẩn', 'Sửa số ngày đặt hàng của mã chất chuẩn trong danh mục công ty'],
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
