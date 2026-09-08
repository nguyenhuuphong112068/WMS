<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ hành động Từ Chối ở nhóm Danh Mục (chỉ còn Duyệt) - gỡ nút "Từ chối"
 * khỏi giao diện danh mục vật tư/hoá chất/chất chuẩn nên các quyền tương ứng
 * không còn dùng đến, cần xoá khỏi bảng permissions.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [2, 'category_material_reject', 'Từ Chối Danh Mục Vật Tư', 'Đánh dấu từ chối mã vật tư'],
        [2, 'category_chemical_reject', 'Từ Chối Danh Mục Hoá Chất', 'Đánh dấu từ chối mã hoá chất'],
        [2, 'category_standard_reject', 'Từ Chối Danh Mục Chất Chuẩn', 'Đánh dấu từ chối mã chất chuẩn'],
    ];

    /** Nhóm quyền được cấp sẵn toàn bộ quyền khi cài đặt */
    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down(): void
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
};
