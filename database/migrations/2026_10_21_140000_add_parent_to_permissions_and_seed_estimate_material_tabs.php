<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QUYỀN CON (sub-permission): permissions.parent_id trỏ về quyền cha.
 * Quyền con chỉ có hiệu lực khi user cũng có quyền cha (user_permission_names() lọc), trang
 * Nhóm Quyền hiển thị quyền con thụt vào ngay dưới quyền cha.
 *
 * Áp dụng đầu tiên: 5 tab của màn Dự Trù Vật Tư, quyền cha estimate_material_view.
 * Để không ai mất tab đang dùng, nhóm quyền đang có estimate_material_view được cấp đủ 5 tab;
 * riêng "Nhân Viên Cung Ứng Vật Tư" chỉ được Theo dõi dự trù / Bộ phận mua hàng / Đơn đã phê duyệt.
 */
return new class extends Migration
{
    private const PARENT = 'estimate_material_view';

    /** [name, display_name, description] */
    private const TABS = [
        ['estimate_material_tab_list', 'Tab Danh Sách Phiếu', 'Dự Trù Vật Tư - tab "Danh sách phiếu" dự trù của phòng'],
        ['estimate_material_tab_tracking', 'Tab Theo Dõi Dự Trù', 'Dự Trù Vật Tư - tab "Theo dõi dự trù" các mục đã duyệt của phòng'],
        ['estimate_material_tab_watchlist', 'Tab Danh Sách Vật Tư Cần Dự Trù', 'Dự Trù Vật Tư - tab "Danh sách vật tư cần dự trù"'],
        ['estimate_material_tab_purchasing', 'Tab Bộ Phận Mua Hàng', 'Dự Trù Vật Tư - tab "Bộ phận mua hàng" (còn cần đang chọn phòng mua hàng + quyền Tiếp Nhận Xử Lý)'],
        ['estimate_material_tab_approved', 'Tab Đơn Dự Trù Đã Được Phê Duyệt', 'Dự Trù Vật Tư - tab "Đơn dự trù đã được phê duyệt" (còn cần đang chọn phòng mua hàng + quyền Tiếp Nhận Xử Lý)'],
    ];

    private const PURCHASING_ROLE = 'Nhân Viên Cung Ứng Vật Tư';

    private const PURCHASING_ROLE_TABS = [
        'estimate_material_tab_tracking',
        'estimate_material_tab_purchasing',
        'estimate_material_tab_approved',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('permissions', 'parent_id')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('permission_group'); // -> permissions.id
                $table->index('parent_id');
            });
        }

        $parent = DB::table('permissions')->where('name', self::PARENT)->first();

        if (! $parent) {
            return;
        }

        $now = now();

        foreach (self::TABS as $tab) {
            DB::table('permissions')->updateOrInsert(['name' => $tab[0]], [
                'permission_group' => $parent->permission_group,
                'parent_id' => $parent->id,
                'display_name' => $tab[1],
                'description' => $tab[2],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $tabIds = DB::table('permissions')->whereIn('name', array_column(self::TABS, 0))->pluck('id', 'name');
        $purchasingRoleId = DB::table('roles')->where('name', self::PURCHASING_ROLE)->value('id');

        $roleIds = DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id')
            ->merge(DB::table('roles')->where('name', 'Admin')->pluck('id'))
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($tabIds as $name => $permissionId) {
                if ((int) $roleId === (int) $purchasingRoleId && ! in_array($name, self::PURCHASING_ROLE_TABS, true)) {
                    continue;
                }

                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_column(self::TABS, 0))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        if (Schema::hasColumn('permissions', 'parent_id')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
