<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bổ sung các quyền còn thiếu cho những nút thao tác đã có trên giao diện
 * nhưng chưa được khai báo ở lần seed đầu (2026_08_30_090100_seed_wms_permissions).
 * Nhóm quyền (permission_groups) giữ nguyên, chỉ thêm bản ghi vào bảng permissions.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        // ----- 1. Dữ Liệu Gốc -----
        [1, 'materData_approve', 'Duyệt Dữ Liệu Gốc', 'Duyệt bản ghi dữ liệu gốc để dùng ở màn hình nghiệp vụ'],
        [1, 'materData_reject', 'Từ Chối Dữ Liệu Gốc', 'Đánh dấu từ chối bản ghi dữ liệu gốc'],

        // ----- 2. Danh Mục -----
        [2, 'category_material_approve', 'Duyệt Danh Mục Vật Tư', 'Duyệt mã vật tư trong danh mục'],
        [2, 'category_material_reject', 'Từ Chối Danh Mục Vật Tư', 'Đánh dấu từ chối mã vật tư'],
        [2, 'category_material_dept_manage', 'Quản Lý Định Mức Vật Tư Theo Phòng Ban', 'Thêm / sửa / khoá định mức vật tư của phòng ban'],
        [2, 'category_chemical_approve', 'Duyệt Danh Mục Hoá Chất', 'Duyệt mã hoá chất trong danh mục'],
        [2, 'category_chemical_reject', 'Từ Chối Danh Mục Hoá Chất', 'Đánh dấu từ chối mã hoá chất'],
        [2, 'category_chemical_dept_manage', 'Quản Lý Định Mức Hoá Chất Theo Phòng Ban', 'Thêm / sửa / khoá định mức hoá chất của phòng ban'],
        [2, 'category_standard_approve', 'Duyệt Danh Mục Chất Chuẩn', 'Duyệt mã chất chuẩn trong danh mục'],
        [2, 'category_standard_reject', 'Từ Chối Danh Mục Chất Chuẩn', 'Đánh dấu từ chối mã chất chuẩn'],
        [2, 'category_standard_dept_manage', 'Quản Lý Định Mức Chất Chuẩn Theo Phòng Ban', 'Thêm / sửa / khoá định mức chất chuẩn của phòng ban'],

        // ----- 3. Nhập -----
        [3, 'import_material_label', 'In Nhãn Vật Tư', 'In nhãn cho phiếu nhập vật tư'],
        [3, 'import_chemical_label', 'In Nhãn Hoá Chất', 'In nhãn cho phiếu nhập hoá chất'],
        [3, 'import_standard_label', 'In Nhãn Chất Chuẩn', 'In nhãn cho phiếu nhập chất chuẩn'],
        [3, 'import_chemical_receive', 'Nhận Hoá Chất Chuyển Kho', 'Xác nhận nhận hoá chất từ phiếu chuyển kho'],
        [3, 'import_chemical_rejectTransfer', 'Từ Chối Phiếu Chuyển Hoá Chất', 'Từ chối phiếu chuyển kho hoá chất'],

        // ----- 4. Sử Dụng -----
        [4, 'export_chemical_transfer', 'Lập Phiếu Chuyển Hoá Chất', 'Tạo phiếu chuyển kho từ đề nghị cấp phát hoá chất'],
        [4, 'export_chemical_disposal_view', 'Xem Huỷ Hoá Chất', 'Mở tab quản lý huỷ hoá chất'],
        [4, 'export_chemical_disposal_manage', 'Lập / Sửa Đợt Huỷ Hoá Chất', 'Tạo đợt huỷ, thêm - gỡ phiếu, sửa và khoá đợt huỷ'],
        [4, 'export_chemical_disposal_decide', 'Quyết Định & Hoàn Tất Huỷ Hoá Chất', 'Ra quyết định huỷ và xác nhận hoàn tất đợt huỷ'],

        // ----- 5. Tồn Kho -----
        [5, 'inventory_material_balancing', 'Cân Đối Tồn Kho Vật Tư', 'Điều chỉnh cân đối số liệu tồn kho vật tư'],
        [5, 'inventory_chemical_balancing', 'Cân Đối Tồn Kho Hoá Chất', 'Điều chỉnh cân đối số liệu tồn kho hoá chất'],
        [5, 'inventory_standard_balancing', 'Cân Đối Tồn Kho Chất Chuẩn', 'Điều chỉnh cân đối số liệu tồn kho chất chuẩn'],
        [5, 'inventory_material_stocktake', 'Kiểm Kê Kho Vật Tư', 'Mở kỳ kiểm kê, đếm và chốt kết quả kiểm kê vật tư'],
        [5, 'inventory_chemical_internalExpiry', 'Đặt Hạn Dùng Nội Bộ Hoá Chất', 'Thiết lập hạn dùng nội bộ cho lô hoá chất'],
        [5, 'inventory_standard_internalExpiry', 'Đặt Hạn Dùng Nội Bộ Chất Chuẩn', 'Thiết lập hạn dùng nội bộ cho lô chất chuẩn'],
        [5, 'inventory_standard_weight', 'Ghi Chú Cân Chất Chuẩn', 'Ghi nhận ghi chú khối lượng cân của chất chuẩn'],

        // ----- 6. Dự Trù -----
        [6, 'estimate_material_delete', 'Huỷ Phiếu Dự Trù Vật Tư', 'Huỷ phiếu dự trù vật tư'],
        [6, 'estimate_material_tracking', 'Theo Dõi Giao Hàng Dự Trù Vật Tư', 'Xác nhận đã giao / không cần dự trù nữa'],
        [6, 'estimate_chemical_delete', 'Huỷ Phiếu Dự Trù Hoá Chất', 'Huỷ phiếu dự trù hoá chất'],
        [6, 'estimate_chemical_tracking', 'Theo Dõi Giao Hàng Dự Trù Hoá Chất', 'Xác nhận đã giao / không cần dự trù nữa'],
        [6, 'estimate_standard_delete', 'Huỷ Phiếu Dự Trù Chất Chuẩn', 'Huỷ phiếu dự trù chất chuẩn'],
        [6, 'estimate_standard_tracking', 'Theo Dõi Giao Hàng Dự Trù Chất Chuẩn', 'Xác nhận đã giao / không cần dự trù nữa'],

        // ----- 7. Đánh Giá Hạn Dùng -----
        [7, 'stability_standard_assess', 'Thực Hiện Đánh Giá Chuẩn Thứ Cấp', 'Nhập kết quả đánh giá cho từng đợt'],
        [7, 'stability_standard_issue', 'Cấp Phát Chuẩn Thứ Cấp', 'Cấp phát chuẩn thứ cấp đã đánh giá đạt'],
        [7, 'stability_standard_delete', 'Xoá Hồ Sơ Đánh Giá Chuẩn Thứ Cấp', 'Xoá dòng đánh giá trong hồ sơ chuẩn thứ cấp'],
    ];

    /** Nhóm quyền được cấp sẵn toàn bộ quyền khi cài đặt */
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
    }
};
