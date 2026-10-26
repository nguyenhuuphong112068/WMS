<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Màn hình "Tồn - Đối Chiếu Ngưỡng" (Ngưỡng Tồn Trữ PL IV) gộp vào Tồn Kho Hoá Chất thành tab
 * "Đối chiếu ngưỡng PL IV". Màn hình cũ dùng chung quyền inventory_chemical_view nên
 * PageTabs::seed() tạo quyền tab inventory_chemical_tab_threshold và cấp cho mọi vai trò đang
 * xem được Tồn Kho Hoá Chất - không ai mất quyền.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageTabs::seed();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'inventory_chemical_tab_threshold')->value('id');

        if ($id) {
            DB::table('role_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
