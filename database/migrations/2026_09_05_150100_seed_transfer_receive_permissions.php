<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền cho bước "Nhận" / "Từ chối nhận" mới ở tab Đề nghị liên phòng ban (Sử Dụng),
 * bổ sung theo mẫu 2026_09_02_110000_seed_wms_action_permissions.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_chemical_transfer_receive', 'Nhận Chuyển Hoá Chất Liên Phòng Ban', 'Xác nhận nhận hoá chất do phòng khác cấp phát, chọn vị trí lưu'],
        [4, 'export_chemical_transfer_return', 'Từ Chối Nhận Chuyển Hoá Chất', 'Từ chối nhận hoá chất đã được cấp phát, hoàn tồn lại cho phòng gửi'],
        [4, 'export_standard_transfer_receive', 'Nhận Chuyển Chất Chuẩn Liên Phòng Ban', 'Xác nhận nhận chất chuẩn do phòng khác cấp phát, chọn vị trí lưu'],
        [4, 'export_standard_transfer_return', 'Từ Chối Nhận Chuyển Chất Chuẩn', 'Từ chối nhận chất chuẩn đã được cấp phát, hoàn tồn lại cho phòng gửi'],
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
