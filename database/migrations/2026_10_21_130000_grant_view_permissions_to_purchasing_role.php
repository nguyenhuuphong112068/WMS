<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vai trò "Nhân Viên Cung Ứng Vật Tư" được XEM toàn bộ màn hình nghiệp vụ (Dữ Liệu Gốc, Danh Mục,
 * Nhập, Sử Dụng, Tồn Kho, Dự Trù, Đánh Giá Hạn Dùng) để tra cứu khi xử lý dự trù.
 * Không cấp các quyền xem nhóm Quản Trị (user, nhóm quyền, Audit Trail, lịch sử đăng nhập).
 */
return new class extends Migration
{
    private const ROLE = 'Nhân Viên Cung Ứng Vật Tư';

    private const PERMISSIONS = [
        'materData_material_view', 'materData_chemical_view', 'materData_standard_view', 'materData_common_view',
        'category_material_view', 'category_chemical_view', 'category_standard_view',
        'import_material_view', 'import_chemical_view', 'import_standard_view',
        'export_material_view', 'export_chemical_view', 'export_standard_view', 'export_chemical_disposal_view',
        'inventory_material_view', 'inventory_chemical_view', 'inventory_standard_view',
        'estimate_material_view', 'estimate_chemical_view', 'estimate_standard_view',
        'stability_standard_view', 'stability_plan_view',
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ], []);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if (! $roleId) {
            return;
        }

        // Giữ lại quyền Xem Dự Trù - thuộc bộ quyền gốc của vai trò (2026_10_21_120000)
        $ids = DB::table('permissions')
            ->whereIn('name', array_diff(self::PERMISSIONS, ['estimate_material_view', 'estimate_chemical_view', 'estimate_standard_view']))
            ->pluck('id');

        DB::table('role_permission')->where('role_id', $roleId)->whereIn('permission_id', $ids)->delete();
    }
};
