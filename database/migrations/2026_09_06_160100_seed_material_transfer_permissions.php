<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền cho tab "Đề nghị chuyển liên phòng ban" của màn Sử Dụng Vật Tư, bổ sung theo
 * mẫu 2026_09_05_150100_seed_transfer_receive_permissions của hoá chất / chất chuẩn.
 *
 * Việc LẬP đề nghị liên phòng ban dùng lại quyền export_material_request đã có (cùng là
 * thao tác đề nghị lấy vật tư), nên ở đây chỉ thêm ba quyền của phía cấp phát và nhận.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_transfer', 'Cấp Phát Vật Tư Liên Phòng Ban', 'Chọn mã xuất nhập của phòng mình để cấp phát cho phòng khác, hoặc từ chối cấp'],
        [4, 'export_material_transfer_receive', 'Nhận Chuyển Vật Tư Liên Phòng Ban', 'Xác nhận nhận vật tư do phòng khác cấp phát, chọn định khu lưu'],
        [4, 'export_material_transfer_return', 'Từ Chối Nhận Chuyển Vật Tư', 'Từ chối nhận vật tư đã được cấp phát, hoàn tồn lại cho phòng gửi'],
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
