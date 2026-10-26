<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vai trò "Người dùng Chất Chuẩn" (nhân viên các bộ phận sử dụng chất chuẩn) - tương tự
 * "Người dùng Hoá chất": XEM toàn bộ màn hình nghiệp vụ chất chuẩn (kèm mọi tab), lập đề nghị
 * cấp phát chuẩn cho Tổ, ghi Sử dụng chất chuẩn (nút "Sử dụng chất chuẩn" gắn quyền
 * export_standard_issue) và trao đổi ở tab Theo dõi dự trù.
 * Không cấp quyền thêm/sửa danh mục, nhập kho, cân đối, hạn dùng, ký duyệt hay mua hàng.
 */
return new class extends Migration
{
    private const ROLE = 'Người dùng Chất Chuẩn';

    private const PERMISSIONS = [
        // Dữ Liệu Gốc
        'materData_standard_view', 'materData_common_view',
        // Danh Mục Chất Chuẩn
        'category_standard_view', 'category_standard_tab_company', 'category_standard_tab_department',
        // Nhập Chất Chuẩn
        'import_standard_view', 'import_standard_tab_check', 'import_standard_tab_book',
        // Sử Dụng Chất Chuẩn
        'export_standard_view', 'export_standard_tab_request', 'export_standard_tab_transfer', 'export_standard_tab_book',
        'export_standard_request', 'export_standard_issue',
        // Tồn Kho Chất Chuẩn
        'inventory_standard_view', 'inventory_standard_tab_standard', 'inventory_standard_tab_code',
        'inventory_standard_tab_zone', 'inventory_standard_tab_expiring', 'inventory_standard_tab_internal',
        'inventory_standard_tab_weight', 'inventory_export_excel',
        // Dự Trù Chất Chuẩn
        'estimate_standard_view', 'estimate_standard_tab_list', 'estimate_standard_tab_watchlist',
        'estimate_standard_tab_approved', 'estimate_standard_tab_tracking', 'estimate_standard_chat',
        // Đánh Giá Hạn Dùng
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

        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('role_permission')->where('role_id', $roleId)->whereIn('permission_id', $ids)->delete();
    }
};
