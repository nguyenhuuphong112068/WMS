<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * TÁCH NHÓM QUYỀN VPP khỏi Vật Tư: mỗi nhóm quyền chuyên Vật Tư (Quản lý Kho, Tổ Trưởng Quản Lý Kho,
 * Người Đề nghị, Người hiệu chuẩn) được nhân bản thành nhóm "... VPP" chỉ giữ quyền *vpp*.
 * - Nhóm Vật Tư gốc bỏ quyền VPP, loại phụ trách chỉ còn Vật Tư; nhóm VPP mới phụ trách VPP.
 * - Người đang giữ nhóm Vật Tư được gán thêm nhóm VPP tương ứng (user_role) để không mất thao tác.
 * - Trình Ký Đề Nghị CP VPP đang dùng vai trò Vật Tư thì chuyển sang vai trò VPP.
 */
return new class extends Migration
{
    private const SOURCES = [4, 8, 26, 30];

    public function up(): void
    {
        $now = now();
        $vppPermissionIds = DB::table('permissions')->where('name', 'like', '%vpp%')->pluck('id')->all();
        if (! $vppPermissionIds) {
            return;
        }

        $map = [];
        foreach (DB::table('roles')->whereIn('id', self::SOURCES)->get() as $role) {
            $name = preg_replace('/vật tư/iu', 'VPP', $role->name);
            $newId = DB::table('roles')->where('name', $name)->value('id');
            if (! $newId) {
                $newId = DB::table('roles')->insertGetId([
                    'name' => $name,
                    'description' => $role->description === null ? null : preg_replace('/vật tư/iu', 'VPP', $role->description),
                    'kinds' => 'vpp',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $map[$role->id] = (int) $newId;

            // Quyền VPP sang nhóm mới, gỡ khỏi nhóm Vật Tư
            foreach (DB::table('role_permission')->where('role_id', $role->id)->whereIn('permission_id', $vppPermissionIds)->pluck('permission_id') as $pid) {
                $row = ['role_id' => $newId, 'permission_id' => $pid];
                if (! DB::table('role_permission')->where($row)->exists()) {
                    DB::table('role_permission')->insert($row);
                }
            }
            DB::table('role_permission')->where('role_id', $role->id)->whereIn('permission_id', $vppPermissionIds)->delete();

            $kinds = array_values(array_diff(array_filter(explode(',', (string) $role->kinds)), ['vpp']));
            DB::table('roles')->where('id', $role->id)->update(['kinds' => implode(',', $kinds ?: ['material'])]);
        }

        // Người dùng: nhóm chính hoặc nhóm gán thêm đều được gán thêm nhóm VPP
        foreach ($map as $oldId => $newId) {
            foreach (DB::table('user_role')->where('role_id', $oldId)->get() as $row) {
                $this->grant($row->user_id, $newId, $row->department_id, $now);
            }
            foreach (DB::table('user_management')->where('role_id', $oldId)->pluck('id') as $userId) {
                $this->grant($userId, $newId, null, $now);
            }
        }

        // Trình ký đề nghị cấp phát VPP theo vai trò VPP
        foreach ($map as $oldId => $newId) {
            DB::table('vpp_request_sign_rules')->where('role_id', $oldId)->update(['role_id' => $newId]);
        }
    }

    public function down(): void
    {
        // Không hoàn tác: nhóm quyền VPP đã có thể được quản trị chỉnh sửa
    }

    private function grant($userId, int $roleId, $departmentId, $now): void
    {
        $row = ['user_id' => $userId, 'role_id' => $roleId, 'department_id' => $departmentId];
        if (! DB::table('user_role')->where($row)->exists()) {
            DB::table('user_role')->insert($row + ['created_at' => $now, 'updated_at' => $now]);
        }
    }
};
