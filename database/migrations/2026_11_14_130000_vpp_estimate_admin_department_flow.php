<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DỰ TRÙ VPP = VIỆC NỘI BỘ CỦA PHÒNG HÀNH CHÁNH (14/11/2026).
 *
 * Chỉ phòng Hành Chánh (deparments.purchasing_key = 'admin', giữ kho VPP) lập dự trù VPP; ký
 * Quản Lý Bộ Phân phòng Hành Chánh -> Ban Giám Đốc có phạm vi; chính phòng Hành Chánh tiếp nhận
 * xử lý. Lập / sửa / huỷ phiếu + theo dõi giao hàng chỉ còn ở 2 vai trò kho VPP (bỏ khỏi Quản Lý
 * Bộ Phân - QLBP chỉ ký duyệt). Quyền tiếp nhận xử lý + tab "Đơn dự trù đã được phê duyệt" của VPP vì thế chuyển từ
 * "Nhân Viên Cung Ứng Vật Tư" sang 2 vai trò kho VPP (Quản lý / Tổ trưởng Quản lý Kho VPP);
 * sửa diễn giải các quyền dự trù VPP cho khớp.
 */
return new class extends Migration
{
    private const MOVED = ['estimate_vpp_purchasing', 'estimate_vpp_tab_approved'];

    private const FROM_ROLES = ['Nhân Viên Cung Ứng Vật Tư'];

    private const TO_ROLES = ['Quan lý Kho VPP', 'Quản Lý Kho VPP', 'Tổ Tưởng Quản Lý Kho VPP', 'Tổ Trưởng Quản Lý Kho VPP'];

    /** Lập / sửa / huỷ phiếu + theo dõi giao hàng: chỉ kho VPP; Quản Lý Bộ Phân chỉ còn ký duyệt (estimate_vpp_sign). */
    private const KEEPER_ONLY = ['estimate_vpp_create', 'estimate_vpp_update', 'estimate_vpp_delete', 'estimate_vpp_tracking'];

    private const MANAGER_ROLES = ['Quản Lý Bộ Phân', 'Quản Lý Bộ Phận'];

    private const DESCRIPTIONS = [
        'estimate_vpp_create' => [
            'Lập phiếu dự trù VPP mới (chọn tháng / năm dự trù, ghi chú), kể cả lập nhanh bằng cách tick chọn ở Danh sách VPP cần dự trù. Phiếu mới ở trạng thái Nháp.',
            'Lưu ý: chỉ phòng Hành Chánh (phòng giữ kho VPP) lập được dự trù VPP - đang chọn phòng khác thì nút Lập phiếu dự trù bị khoá.',
        ],
        'estimate_vpp_purchasing' => [
            'Tiếp nhận xử lý dự trù VPP của phòng Hành Chánh: xem tab Đơn dự trù đã được phê duyệt, bấm Tiếp nhận phiếu, cập nhật Ngày hẹn đáp ứng cho từng tháng của từng VPP.',
            'Lưu ý: chỉ có tác dụng khi đang chọn phòng Hành Chánh ở thanh trên cùng. Phòng Hành Chánh vừa lập vừa tiếp nhận nên huỷ mặt hàng chỉ cần một lần xác nhận. Phiếu chờ tiếp nhận hiện ở Trang chủ.',
        ],
        'estimate_vpp_tab_approved' => [
            'Xem tab Đơn dự trù đã được phê duyệt: phiếu dự trù VPP đã duyệt của phòng Hành Chánh chờ tiếp nhận và hẹn ngày đáp ứng.',
            'Lưu ý: tab chỉ hiện khi đang chọn phòng Hành Chánh và có thêm quyền Tiếp Nhận Xử Lý Dự Trù VPP.',
        ],
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', self::MOVED)->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $fromRoleIds = DB::table('roles')->whereIn('name', self::FROM_ROLES)->pluck('id');
        $toRoleIds = DB::table('roles')->whereIn('name', self::TO_ROLES)->pluck('id');

        DB::table('role_permission')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $fromRoleIds)
            ->delete();

        foreach ($toRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
            }
        }

        $keeperPermissionIds = DB::table('permissions')->whereIn('name', self::KEEPER_ONLY)->pluck('id');
        $managerRoleIds = DB::table('roles')->whereIn('name', self::MANAGER_ROLES)->pluck('id');

        DB::table('role_permission')
            ->whereIn('permission_id', $keeperPermissionIds)
            ->whereIn('role_id', $managerRoleIds)
            ->delete();

        foreach (self::DESCRIPTIONS as $name => $lines) {
            DB::table('permissions')->where('name', $name)->update([
                'description' => implode("\n", $lines),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', self::MOVED)->pluck('id');
        $fromRoleIds = DB::table('roles')->whereIn('name', self::FROM_ROLES)->pluck('id');
        $toRoleIds = DB::table('roles')->whereIn('name', self::TO_ROLES)->pluck('id');

        DB::table('role_permission')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $toRoleIds)
            ->delete();

        $keeperPermissionIds = DB::table('permissions')->whereIn('name', self::KEEPER_ONLY)->pluck('id');

        foreach (DB::table('roles')->whereIn('name', self::MANAGER_ROLES)->pluck('id') as $roleId) {
            foreach ($keeperPermissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }

        foreach ($fromRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }
};
