<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền cho nút "Quản Lý Nhóm Quyền" (thêm mới / đổi tên / xoá nhóm quyền)
 * trên màn NHÓM QUYỀN. Thuộc nhóm quyền 8 - Quản Trị. Cấp sẵn cho nhóm Admin.
 */
return new class extends Migration
{
    private const PERMISSION = [8, 'role_manage', 'Tạo / Sửa Nhóm Quyền', 'Thêm mới, đổi tên, xoá nhóm quyền'];

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(['name' => self::PERMISSION[1]], [
            'permission_group' => self::PERMISSION[0],
            'display_name'     => self::PERMISSION[2],
            'description'      => self::PERMISSION[3],
            'updated_at'       => $now,
            'created_at'       => $now,
        ]);

        $permissionId = DB::table('permissions')->where('name', self::PERMISSION[1])->value('id');
        $adminRoleIds = DB::table('roles')->where('name', 'Admin')->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ], []);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', self::PERMISSION[1])->value('id');

        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('role_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
