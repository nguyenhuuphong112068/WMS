<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [3, 'import_chemical_attachment_upload', 'Đính Kèm Thêm File Hoá Chất', 'Tải lên thêm file đính kèm cho phiếu nhập hoá chất'],
        [3, 'import_material_attachment_upload', 'Đính Kèm Thêm File Vật Tư', 'Tải lên thêm file đính kèm cho phiếu nhập vật tư'],
        [3, 'import_standard_attachment_upload', 'Đính Kèm Thêm File Chất Chuẩn', 'Tải lên thêm file đính kèm cho phiếu nhập chất chuẩn'],
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
