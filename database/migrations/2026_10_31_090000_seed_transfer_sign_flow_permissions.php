<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền RIÊNG cho màn Dữ Liệu Gốc › Trình Ký Đề Nghị Liên Phòng Ban (TransferSignFlowController).
 *
 * Trước đây màn này mượn bộ materData_material_* của Tên Vật Tư nên trên màn Nhóm Quyền không có
 * dòng nào mang tên nó, muốn cho ai sửa quy trình ký thì phải cho luôn quyền sửa Tên Vật Tư.
 *
 * Role / user đang có quyền materData_material_<action> được chép sang quyền mới tương ứng để
 * không ai mất quyền sau khi cài; sau đó có thể gỡ riêng ở màn Nhóm Quyền.
 */
return new class extends Migration
{
    private const GROUP_ID = 1; // permission_groups: Dữ Liệu Gốc

    /** action => [display_name, description] */
    private const ACTIONS = [
        'view' => ['Xem Trình Ký Liên Phòng Ban', 'Mở màn Dữ Liệu Gốc › Trình Ký Đề Nghị Liên Phòng Ban'],
        'create' => ['Thêm Bước Ký Liên Phòng Ban', 'Thêm bước ký cho đề nghị liên phòng ban / đề nghị VPP'],
        'update' => ['Sửa Bước Ký Liên Phòng Ban', 'Sửa bước ký cho đề nghị liên phòng ban / đề nghị VPP'],
        'deActive' => ['Khoá Bước Ký Liên Phòng Ban', 'Khoá / mở khoá bước ký cho đề nghị liên phòng ban / đề nghị VPP'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::ACTIONS as $action => [$display, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => "materData_transferSign_{$action}"], [
                'permission_group' => self::GROUP_ID,
                'display_name' => $display,
                'description' => $description,
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            $newId = DB::table('permissions')->where('name', "materData_transferSign_{$action}")->value('id');
            $oldId = DB::table('permissions')->where('name', "materData_material_{$action}")->value('id');

            if ($oldId) {
                foreach (DB::table('role_permission')->where('permission_id', $oldId)->pluck('role_id') as $roleId) {
                    DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $newId], []);
                }

                foreach (DB::table('user_permission')->where('permission_id', $oldId)->get() as $row) {
                    DB::table('user_permission')->updateOrInsert(
                        ['user_id' => $row->user_id, 'permission_id' => $newId],
                        ['is_denied' => $row->is_denied]
                    );
                }
            }

            foreach (DB::table('roles')->where('name', 'Admin')->pluck('id') as $roleId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $newId], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_map(fn ($action) => "materData_transferSign_{$action}", array_keys(self::ACTIONS)))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
