<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem tab "Danh sách hoá chất cần dự trù" / "Danh sách chất chuẩn cần dự trù" (quyền con
 * của estimate_chemical_view / estimate_standard_view) - cùng cơ chế tab "Danh sách vật tư cần
 * dự trù" của Dự Trù Vật Tư. PageTabs::seed() chỉ tạo quyền tab còn thiếu và cấp cho mọi nhóm
 * quyền đang xem được trang; "Nhân Viên Cung Ứng Vật Tư" (bộ phận mua hàng) không lập phiếu dự
 * trù nên không được cấp, giống tab "Danh sách phiếu".
 */
return new class extends Migration
{
    private const SKIP = [
        'Nhân Viên Cung Ứng Vật Tư' => [
            'estimate_chemical' => ['list', 'watchlist'],
            'estimate_standard' => ['list', 'watchlist'],
        ],
    ];

    public function up(): void
    {
        PageTabs::seed(self::SKIP);
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', ['estimate_chemical_tab_watchlist', 'estimate_standard_tab_watchlist'])
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
