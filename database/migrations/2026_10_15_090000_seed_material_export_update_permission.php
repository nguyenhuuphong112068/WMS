<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH SỔ SỬ DỤNG VẬT TƯ - nút "Điều chỉnh" ở tab Sổ sử dụng vật tư (Sử Dụng Vật Tư).
 *
 * Trước đây nút này dùng chung quyền Xuất Kho Vật Tư (export_material_issue) và controller
 * không kiểm tra lại. Phiếu sử dụng đã ghi là trừ tồn nên sửa số lượng / mục đích phải có
 * quyền riêng; MaterialExportController::update() và deActive() chặn lại ở server.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_update', 'Điều Chỉnh Sổ Sử Dụng Vật Tư', 'Sửa số lượng, mục đích, ghi chú của phiếu sử dụng đã ghi ở tab Sổ sử dụng vật tư (mỗi lần sửa lưu lịch sử điều chỉnh)'],
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
