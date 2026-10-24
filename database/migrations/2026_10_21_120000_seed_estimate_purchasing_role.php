<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vai trò "Nhân Viên Cung Ứng Vật Tư" - bộ phận mua hàng tiếp nhận, xử lý phiếu dự trù đã duyệt
 * của mọi phòng ban (Vật Tư / Hoá Chất / Chất Chuẩn).
 *
 * Quyền mới estimate_<loại>_purchasing: thấy tab "Bộ phận mua hàng" của màn Dự Trù, xác nhận /
 * từ chối / đề nghị huỷ mục phía mua hàng (EstimateItemCancel::purchaseCancel) và mục "Đề nghị huỷ
 * mục dự trù" phía mua hàng ở Trang chủ. Vẫn phải đang chọn đúng phòng mua hàng
 * (deparments.purchasing_key) - xem App\Support\MaterialPurchasing.
 *
 * Trước đây tab này chỉ chặn theo phòng ban, nên để không ai mất chức năng đang dùng, quyền mới
 * được cấp sẵn cho Admin và mọi nhóm quyền đang có quyền Xem dự trù tương ứng.
 */
return new class extends Migration
{
    private const ROLE = 'Nhân Viên Cung Ứng Vật Tư';

    private const ROLE_DESCRIPTION = 'Bộ phận mua hàng: tiếp nhận, theo dõi giao hàng, hẹn ngày đáp ứng và xử lý huỷ mục các phiếu dự trù đã duyệt';

    /** [permission_group, name, display_name, description, quyền Xem tương ứng] */
    private const PERMISSIONS = [
        [6, 'estimate_material_purchasing', 'Tiếp Nhận Xử Lý Dự Trù Vật Tư', 'Tab "Bộ phận mua hàng" của Dự Trù Vật Tư: tiếp nhận mục đã duyệt của mọi phòng ban, xác nhận / từ chối / đề nghị huỷ mục phía mua hàng', 'estimate_material_view'],
        [6, 'estimate_chemical_purchasing', 'Tiếp Nhận Xử Lý Dự Trù Hoá Chất', 'Tab "Bộ phận mua hàng" của Dự Trù Hoá Chất: tiếp nhận mục đã duyệt của mọi phòng ban, xác nhận / từ chối / đề nghị huỷ mục phía mua hàng', 'estimate_chemical_view'],
        [6, 'estimate_standard_purchasing', 'Tiếp Nhận Xử Lý Dự Trù Chất Chuẩn', 'Tab "Bộ phận mua hàng" của Dự Trù Chất Chuẩn: tiếp nhận mục đã duyệt của mọi phòng ban, xác nhận / từ chối / đề nghị huỷ mục phía mua hàng', 'estimate_standard_view'],
    ];

    /** Quyền có sẵn cấp thêm cho vai trò: mở màn dự trù. KHÔNG cấp *_tracking (Hoàn thành / Huỷ mục là việc của phòng đề nghị) */
    private const ROLE_EXTRA_PERMISSIONS = [
        'estimate_material_view',
        'estimate_chemical_view',
        'estimate_standard_view',
    ];

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

        // Giữ nguyên chức năng đang dùng: Admin + nhóm quyền đang Xem được loại dự trù đó
        foreach (self::PERMISSIONS as $permission) {
            $permissionId = DB::table('permissions')->where('name', $permission[1])->value('id');

            $roleIds = DB::table('roles')->where('name', 'Admin')->pluck('id')
                ->merge(DB::table('role_permission')
                    ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                    ->where('permissions.name', $permission[4])
                    ->pluck('role_permission.role_id'))
                ->unique();

            foreach ($roleIds as $roleId) {
                $this->grant($roleId, $permissionId);
            }
        }

        DB::table('roles')->updateOrInsert(['name' => self::ROLE], [
            'description' => self::ROLE_DESCRIPTION,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_merge(array_column(self::PERMISSIONS, 1), self::ROLE_EXTRA_PERMISSIONS))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $this->grant($roleId, $permissionId);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if ($roleId) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('user_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        $ids = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function grant($roleId, $permissionId): void
    {
        DB::table('role_permission')->updateOrInsert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ], []);
    }
};
