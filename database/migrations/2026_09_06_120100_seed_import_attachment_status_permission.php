<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền "Đổi Trạng Thái File Đính Kèm" cho từng loại hàng. Một quyền dùng chung cho
 * cả màn hình Nhập lẫn màn hình Tồn Kho vì file đính kèm thuộc về phiếu nhập.
 * Nhóm quyền 3 = Nhập (xem 2026_08_30_090100_seed_wms_permissions).
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [3, 'import_chemical_attachment_status', 'Đổi Trạng Thái File Đính Kèm Hoá Chất', 'Chuyển file đính kèm phiếu nhập hoá chất giữa Đang sử dụng / Ngưng sử dụng'],
        [3, 'import_material_attachment_status', 'Đổi Trạng Thái File Đính Kèm Vật Tư', 'Chuyển file đính kèm phiếu nhập vật tư giữa Đang sử dụng / Ngưng sử dụng'],
        [3, 'import_standard_attachment_status', 'Đổi Trạng Thái File Đính Kèm Chất Chuẩn', 'Chuyển file đính kèm phiếu nhập chất chuẩn giữa Đang sử dụng / Ngưng sử dụng'],
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
