<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền đính kèm file khởi tạo dữ liệu đã điền (màn hình Dữ Liệu Gốc › Khởi Tạo Dữ Liệu).
 * Người có quyền chỉ thấy khối Đính kèm + danh sách file của phòng mình; Admin thấy mọi file và
 * tải file mẫu. Cấp sẵn cho mọi nhóm quyền vì giai đoạn triển khai phòng ban nào cũng phải nộp
 * dữ liệu - Admin gỡ bớt trên màn hình Phân Quyền khi triển khai xong.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [1, 'materData_initialData_upload', 'Đính Kèm File Khởi Tạo Dữ Liệu', 'Đính kèm file Excel khởi tạo dữ liệu đã điền để Admin tổng hợp (không ghi vào hệ thống)'],
    ];

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

        $permissionIds = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 1))->pluck('id');

        foreach (DB::table('roles')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 1))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
