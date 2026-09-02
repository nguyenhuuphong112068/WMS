<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nhóm quyền bám theo các mục cha trên leftNAV.
     * Khoá mảng chính là permission_groups.id để permissions.permission_group trỏ tới.
     */
    private const GROUPS = [
        1 => 'Dữ Liệu Gốc',
        2 => 'Danh Mục',
        3 => 'Nhập',
        4 => 'Sử Dụng',
        5 => 'Tồn Kho',
        6 => 'Dự Trù',
        7 => 'Đánh Giá Hạn Dùng',
        8 => 'Quản Trị',
    ];

    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        // ----- 1. Dữ Liệu Gốc -----
        [1, 'materData_view', 'Xem Dữ Liệu Gốc', 'Mở các màn hình thuộc menu Dữ Liệu Gốc'],
        [1, 'materData_create', 'Thêm Dữ Liệu Gốc', 'Thêm mới bản ghi dữ liệu gốc'],
        [1, 'materData_update', 'Sửa Dữ Liệu Gốc', 'Cập nhật bản ghi dữ liệu gốc'],
        [1, 'materData_deActive', 'Vô Hiệu Dữ Liệu Gốc', 'Vô hiệu hoá bản ghi dữ liệu gốc'],

        // ----- 2. Danh Mục -----
        [2, 'category_material_view', 'Xem Danh Mục Vật Tư', 'Mở màn hình Danh Mục Vật Tư'],
        [2, 'category_material_create', 'Thêm Danh Mục Vật Tư', 'Thêm mã vật tư vào danh mục'],
        [2, 'category_material_update', 'Sửa Danh Mục Vật Tư', 'Cập nhật thông tin mã vật tư'],
        [2, 'category_material_deActive', 'Vô Hiệu Danh Mục Vật Tư', 'Vô hiệu hoá mã vật tư'],
        [2, 'category_chemical_view', 'Xem Danh Mục Hoá Chất', 'Mở màn hình Danh Mục Hoá Chất'],
        [2, 'category_chemical_create', 'Thêm Danh Mục Hoá Chất', 'Thêm mã hoá chất vào danh mục'],
        [2, 'category_chemical_update', 'Sửa Danh Mục Hoá Chất', 'Cập nhật thông tin mã hoá chất'],
        [2, 'category_chemical_deActive', 'Vô Hiệu Danh Mục Hoá Chất', 'Vô hiệu hoá mã hoá chất'],
        [2, 'category_standard_view', 'Xem Danh Mục Chất Chuẩn', 'Mở màn hình Danh Mục Chất Chuẩn'],
        [2, 'category_standard_create', 'Thêm Danh Mục Chất Chuẩn', 'Thêm mã chất chuẩn vào danh mục'],
        [2, 'category_standard_update', 'Sửa Danh Mục Chất Chuẩn', 'Cập nhật thông tin mã chất chuẩn'],
        [2, 'category_standard_deActive', 'Vô Hiệu Danh Mục Chất Chuẩn', 'Vô hiệu hoá mã chất chuẩn'],

        // ----- 3. Nhập -----
        [3, 'import_material_view', 'Xem Phiếu Nhập Vật Tư', 'Mở màn hình Nhập Vật Tư'],
        [3, 'import_material_create', 'Thêm Phiếu Nhập Vật Tư', 'Lập phiếu nhập kho vật tư'],
        [3, 'import_material_update', 'Sửa Phiếu Nhập Vật Tư', 'Điều chỉnh phiếu nhập kho vật tư'],
        [3, 'import_material_delete', 'Huỷ Phiếu Nhập Vật Tư', 'Huỷ / vô hiệu phiếu nhập kho vật tư'],
        [3, 'import_chemical_view', 'Xem Phiếu Nhập Hoá Chất', 'Mở màn hình Nhập Hoá Chất'],
        [3, 'import_chemical_create', 'Thêm Phiếu Nhập Hoá Chất', 'Lập phiếu nhập kho hoá chất'],
        [3, 'import_chemical_update', 'Sửa Phiếu Nhập Hoá Chất', 'Điều chỉnh phiếu nhập kho hoá chất'],
        [3, 'import_chemical_delete', 'Huỷ Phiếu Nhập Hoá Chất', 'Huỷ / vô hiệu phiếu nhập kho hoá chất'],
        [3, 'import_standard_view', 'Xem Phiếu Nhập Chất Chuẩn', 'Mở màn hình Nhập Chất Chuẩn'],
        [3, 'import_standard_create', 'Thêm Phiếu Nhập Chất Chuẩn', 'Lập phiếu nhập kho chất chuẩn'],
        [3, 'import_standard_update', 'Sửa Phiếu Nhập Chất Chuẩn', 'Điều chỉnh phiếu nhập kho chất chuẩn'],
        [3, 'import_standard_delete', 'Huỷ Phiếu Nhập Chất Chuẩn', 'Huỷ / vô hiệu phiếu nhập kho chất chuẩn'],

        // ----- 4. Sử Dụng -----
        [4, 'export_material_view', 'Xem Cấp Phát Vật Tư', 'Mở màn hình Sử Dụng Vật Tư'],
        [4, 'export_material_request', 'Đề Nghị Cấp Phát Vật Tư', 'Lập đề nghị cấp phát vật tư'],
        [4, 'export_material_approve', 'Duyệt Cấp Phát Vật Tư', 'Ký duyệt đề nghị cấp phát vật tư'],
        [4, 'export_material_issue', 'Xuất Kho Vật Tư', 'Thực hiện xuất kho vật tư'],
        [4, 'export_chemical_view', 'Xem Cấp Phát Hoá Chất', 'Mở màn hình Sử Dụng Hoá Chất'],
        [4, 'export_chemical_request', 'Đề Nghị Cấp Phát Hoá Chất', 'Lập đề nghị cấp phát hoá chất'],
        [4, 'export_chemical_approve', 'Duyệt Cấp Phát Hoá Chất', 'Ký duyệt đề nghị cấp phát hoá chất'],
        [4, 'export_chemical_issue', 'Xuất Kho Hoá Chất', 'Thực hiện xuất kho hoá chất'],
        [4, 'export_standard_view', 'Xem Cấp Phát Chất Chuẩn', 'Mở màn hình Sử Dụng Chất Chuẩn'],
        [4, 'export_standard_request', 'Đề Nghị Cấp Phát Chất Chuẩn', 'Lập đề nghị cấp phát chất chuẩn'],
        [4, 'export_standard_approve', 'Duyệt Cấp Phát Chất Chuẩn', 'Ký duyệt đề nghị cấp phát chất chuẩn'],
        [4, 'export_standard_issue', 'Xuất Kho Chất Chuẩn', 'Thực hiện xuất kho chất chuẩn'],

        // ----- 5. Tồn Kho -----
        [5, 'inventory_material_view', 'Xem Tồn Kho Vật Tư', 'Mở màn hình Tồn Kho Vật Tư'],
        [5, 'inventory_chemical_view', 'Xem Tồn Kho Hoá Chất', 'Mở màn hình Tồn Kho Hoá Chất'],
        [5, 'inventory_standard_view', 'Xem Tồn Kho Chất Chuẩn', 'Mở màn hình Tồn Kho Chất Chuẩn'],
        [5, 'inventory_export_excel', 'Xuất Excel Tồn Kho', 'Kết xuất báo cáo tồn kho ra Excel'],

        // ----- 6. Dự Trù -----
        [6, 'estimate_material_view', 'Xem Dự Trù Vật Tư', 'Mở màn hình Dự Trù Vật Tư'],
        [6, 'estimate_material_create', 'Lập Dự Trù Vật Tư', 'Tạo phiếu dự trù vật tư'],
        [6, 'estimate_material_update', 'Sửa Dự Trù Vật Tư', 'Điều chỉnh phiếu dự trù vật tư'],
        [6, 'estimate_material_sign', 'Ký Duyệt Dự Trù Vật Tư', 'Ký các bước duyệt phiếu dự trù vật tư'],
        [6, 'estimate_chemical_view', 'Xem Dự Trù Hoá Chất', 'Mở màn hình Dự Trù Hoá Chất'],
        [6, 'estimate_chemical_create', 'Lập Dự Trù Hoá Chất', 'Tạo phiếu dự trù hoá chất'],
        [6, 'estimate_chemical_update', 'Sửa Dự Trù Hoá Chất', 'Điều chỉnh phiếu dự trù hoá chất'],
        [6, 'estimate_chemical_sign', 'Ký Duyệt Dự Trù Hoá Chất', 'Ký các bước duyệt phiếu dự trù hoá chất'],
        [6, 'estimate_standard_view', 'Xem Dự Trù Chất Chuẩn', 'Mở màn hình Dự Trù Chất Chuẩn'],
        [6, 'estimate_standard_create', 'Lập Dự Trù Chất Chuẩn', 'Tạo phiếu dự trù chất chuẩn'],
        [6, 'estimate_standard_update', 'Sửa Dự Trù Chất Chuẩn', 'Điều chỉnh phiếu dự trù chất chuẩn'],
        [6, 'estimate_standard_sign', 'Ký Duyệt Dự Trù Chất Chuẩn', 'Ký các bước duyệt phiếu dự trù chất chuẩn'],

        // ----- 7. Đánh Giá Hạn Dùng -----
        [7, 'stability_standard_view', 'Xem Đánh Giá Chuẩn Thứ Cấp', 'Mở màn hình Chuẩn Thứ Cấp'],
        [7, 'stability_standard_create', 'Thêm Đánh Giá Chuẩn Thứ Cấp', 'Tạo hồ sơ đánh giá chuẩn thứ cấp'],
        [7, 'stability_standard_update', 'Sửa Đánh Giá Chuẩn Thứ Cấp', 'Cập nhật hồ sơ đánh giá chuẩn thứ cấp'],
        [7, 'stability_plan_view', 'Xem Kế Hoạch Đánh Giá', 'Mở màn hình Kế Hoạch Đánh Giá'],
        [7, 'stability_plan_create', 'Thêm Kế Hoạch Đánh Giá', 'Tạo kế hoạch đánh giá hạn dùng'],
        [7, 'stability_plan_update', 'Sửa Kế Hoạch Đánh Giá', 'Cập nhật kế hoạch đánh giá hạn dùng'],

        // ----- 8. Quản Trị -----
        [8, 'user_view', 'Xem Danh Sách User', 'Mở màn hình User'],
        [8, 'user_create', 'Thêm User', 'Tạo tài khoản người dùng'],
        [8, 'user_update', 'Sửa User', 'Cập nhật thông tin / nhóm quyền của user'],
        [8, 'user_deActive', 'Vô Hiệu User', 'Vô hiệu hoá tài khoản người dùng'],
        [8, 'role_view', 'Xem Nhóm Quyền', 'Mở màn hình Nhóm Quyền'],
        [8, 'role_update', 'Phân Quyền Cho Nhóm', 'Bật / tắt quyền của một nhóm quyền'],
        [8, 'permission_view', 'Xem Danh Sách Quyền', 'Mở màn hình Quyền'],
        [8, 'userPermission_manage', 'Cấp Quyền Riêng Cho User', 'Cho phép / từ chối một quyền riêng cho từng user'],
        [8, 'auditTrail_view', 'Xem Lịch Sử Đăng Nhập', 'Mở màn hình Audit Trail'],
    ];

    /** Nhóm quyền được cấp sẵn toàn bộ quyền khi cài đặt */
    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        $now = now();

        foreach (self::GROUPS as $id => $name) {
            DB::table('permission_groups')->updateOrInsert(['id' => $id], [
                'name' => $name,
                'sort_order' => $id,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

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

        // updateOrInsert để chạy lại migration không sinh dòng trùng trong role_permission
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
        DB::table('permission_groups')->whereIn('id', array_keys(self::GROUPS))->delete();
    }
};
