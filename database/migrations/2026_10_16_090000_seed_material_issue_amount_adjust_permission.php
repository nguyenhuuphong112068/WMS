<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền ĐIỀU CHỈNH SỐ LƯỢNG khi cấp phát vật tư (tab Cấp Phát + phiếu chi tiết đề nghị ở Sử Dụng Vật Tư).
 *
 * Hệ thống đề xuất số lượng cấp theo FEFO/FIFO (App\Support\MaterialPicking::plan): bằng số còn
 * phải cấp, kho thiếu thì bằng phần kho còn cấp được. Người KHÔNG có quyền này phải cấp đúng số
 * đề xuất (ô số lượng bị khoá); muốn cấp một phần / cấp khác số đề xuất thì phải có quyền.
 * Controller chặn lại ở issueStore() nên không lách được bằng cách sửa form.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_issue_amount_adjust', 'Điều Chỉnh Số Lượng Khi Cấp Phát Vật Tư', 'Sửa số lượng cấp phát khác số hệ thống đề xuất (cấp một phần, cấp khác số đề nghị)'],
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
