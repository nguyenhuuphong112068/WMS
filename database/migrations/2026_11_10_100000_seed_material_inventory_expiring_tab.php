<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem tab "Cận hạn dùng" của Tồn Kho Vật Tư (quyền con của inventory_material_view) - cùng
 * cơ chế tab Cận hạn dùng của Tồn Kho Hoá Chất / Chất Chuẩn. PageTabs::seed() chỉ tạo quyền tab còn
 * thiếu và cấp cho mọi nhóm quyền đang xem được trang.
 */
return new class extends Migration
{
    public function up(): void
    {
        PageTabs::seed();

        DB::table('permissions')->where('name', 'inventory_material_tab_expiring')->update([
            'description' => 'Xem tab Cận hạn dùng: các lô có hạn rơi vào 6 tháng tới hoặc đã quá hạn, lọc nhanh theo mốc ≤ 3 tháng / 1 tháng / 2 tuần / 1 tuần.',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('name', 'inventory_material_tab_expiring')->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
