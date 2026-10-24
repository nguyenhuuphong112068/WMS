<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem Lịch Sử Đăng Nhập toàn hệ thống (LoginHistoryController::index).
 * Trang "Của tôi" không cần quyền — ai đăng nhập cũng xem được lịch sử của chính mình.
 * Cấp sẵn cho Admin và các nhóm quyền đang có auditTrail_view; auditTrail_view đổi tên hiển thị
 * thành "Xem Audit Trail" vì trước đây bị đặt nhầm là "Xem Lịch Sử Đăng Nhập".
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [8, 'login_history_view', 'Xem Lịch Sử Đăng Nhập', 'Lượt đăng nhập của mọi tài khoản (thành công / sai mật khẩu / bị khoá), phiên đang mở, xuất Excel'],
    ];

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

        DB::table('permissions')->where('name', 'auditTrail_view')
            ->update(['display_name' => 'Xem Audit Trail', 'updated_at' => $now]);

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id')
            ->merge(DB::table('role_permission')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->where('permissions.name', 'auditTrail_view')
                ->pluck('role_permission.role_id'))
            ->unique();

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

        DB::table('permissions')->where('name', 'auditTrail_view')
            ->update(['display_name' => 'Xem Lịch Sử Đăng Nhập']);
    }
};
