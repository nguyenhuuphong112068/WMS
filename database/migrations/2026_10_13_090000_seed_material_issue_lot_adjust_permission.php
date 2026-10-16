<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH MÃ XUẤT NHẬP khi cấp phát vật tư (modal chi tiết đề nghị ở Sử Dụng Vật Tư).
 *
 * Hệ thống tự chia lô theo FEFO/FIFO (App\Support\MaterialPicking::plan). Người cấp phát
 * KHÔNG có quyền này chỉ được cấp đúng các lô hệ thống đề xuất (vẫn sửa được số lượng để
 * cấp một phần); muốn đổi lô, thêm lô khác hay mở danh mục tồn để tự chọn thì phải có quyền.
 * Controller chặn lại ở issueStore() nên không lách được bằng cách sửa form.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_issue_lot_adjust', 'Điều Chỉnh Mã Xuất Nhập Khi Cấp Phát Vật Tư', 'Đổi mã xuất nhập (lô) hệ thống đề xuất theo FEFO/FIFO, thêm lô khác khi cấp phát vật tư'],
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
