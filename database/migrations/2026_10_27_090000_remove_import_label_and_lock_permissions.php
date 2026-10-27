<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ 2 chức năng ở nhóm menu Nhập:
 *   - In nhãn ống chuẩn ở sổ Nhập Chất Chuẩn (route standardImport.label đã xoá)
 *     -> quyền import_standard_label.
 *   - Nút Khoá / Mở khoá phiếu nhập ở sổ nhập Vật Tư, Hoá Chất, Chất Chuẩn (route
 *     *Import.deActive đã xoá) -> quyền import_*_delete chỉ dùng cho nút này.
 *
 * In nhãn vật tư / hoá chất (import_material_label, import_chemical_label) giữ nguyên.
 * down() tạo lại quyền và chỉ cấp lại cho Admin như lúc seed ban đầu.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [3, 'import_material_delete', 'Huỷ Phiếu Nhập Vật Tư', 'Huỷ / vô hiệu phiếu nhập kho vật tư'],
        [3, 'import_chemical_delete', 'Huỷ Phiếu Nhập Hoá Chất', 'Huỷ / vô hiệu phiếu nhập kho hoá chất'],
        [3, 'import_standard_delete', 'Huỷ Phiếu Nhập Chất Chuẩn', 'Huỷ / vô hiệu phiếu nhập kho chất chuẩn'],
        [3, 'import_standard_label', 'In Nhãn Chất Chuẩn', 'In nhãn cho phiếu nhập chất chuẩn'],
    ];

    public function up(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::PERMISSIONS, 1))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    public function down(): void
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

        foreach (DB::table('roles')->where('name', 'Admin')->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }
};
