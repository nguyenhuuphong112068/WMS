<?php

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gộp 2 nhóm quyền tổ trưởng kho: bỏ "Tổ Tưởng Quản Lý Kho Hoá Chất", đổi "Tổ Tưởng Quản Lý Kho Chất Chuẩn" thành
 * "Tổ Tưởng Quản Lý Kho Hoá chất - Chất chuẩn" (loại phụ trách Hoá Chất + Chất Chuẩn). Quyền và người dùng của nhóm bị
 * bỏ chuyển sang nhóm giữ lại (lúc gộp hai nhóm có cùng 35 quyền, dòng gán người dùng của cả hai trỏ tới user đã xoá).
 */
return new class extends Migration
{
    private const DROP = 'Tổ Tưởng Quản Lý Kho Hoá Chất';
    private const KEEP = 'Tổ Tưởng Quản Lý Kho Chất Chuẩn';
    private const NEW_NAME = 'Tổ Tưởng Quản Lý Kho Hoá chất - Chất chuẩn';
    private const ACTOR = 'Gộp nhóm quyền tổ trưởng kho (system)';

    public function up(): void
    {
        $keep = DB::table('roles')->where('name', self::KEEP)->first();
        $drop = DB::table('roles')->where('name', self::DROP)->first();
        if (! $keep) {
            return;
        }

        if ($drop) {
            foreach (DB::table('role_permission')->where('role_id', $drop->id)->pluck('permission_id') as $permissionId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $keep->id, 'permission_id' => $permissionId], []);
            }

            DB::table('user_management')->where('role_id', $drop->id)->update(['role_id' => $keep->id]);
            foreach (DB::table('user_role')->where('role_id', $drop->id)->get() as $row) {
                $exists = DB::table('user_role')->where([
                    'user_id' => $row->user_id, 'role_id' => $keep->id, 'department_id' => $row->department_id,
                ])->exists();
                $exists
                    ? DB::table('user_role')->where('id', $row->id)->delete()
                    : DB::table('user_role')->where('id', $row->id)->update(['role_id' => $keep->id, 'updated_at' => now()]);
            }
            DB::table('material_request_sign_rules')->where('role_id', $drop->id)->update(['role_id' => $keep->id]);
            DB::table('material_transfer_sign_steps')->where('role_id', $drop->id)->update(['role_id' => $keep->id]);

            DB::table('role_permission')->where('role_id', $drop->id)->delete();
            DB::table('roles')->where('id', $drop->id)->delete();
            AuditTrialController::log('Xoá', 'roles', $drop->id, $drop->name, 'Gộp vào nhóm quyền: ' . self::NEW_NAME, self::ACTOR);
        }

        DB::table('roles')->where('id', $keep->id)->update([
            'name' => self::NEW_NAME,
            'description' => self::NEW_NAME,
            'kinds' => 'chemical,standard',
            'updated_at' => now(),
        ]);
        AuditTrialController::log('Cập nhật', 'roles', $keep->id, $keep->name, 'Sửa nhóm quyền: ' . self::NEW_NAME, self::ACTOR);
    }

    public function down(): void
    {
        // Không tách lại được nhóm quyền đã gộp
    }
};
