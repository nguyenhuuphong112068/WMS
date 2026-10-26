<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nhóm menu "Đánh Giá Hạn Dùng" (Chuẩn Thứ Cấp + Kế Hoạch Đánh Giá) gộp vào màn hình Tồn Kho
 * Chất Chuẩn thành hai tab "Đánh giá hạn dùng CTC" / "Kế hoạch đánh giá".
 *
 * Hai tab dùng lại đúng quyền xem cũ (stability_standard_view / stability_plan_view, khai ở
 * PageTabs với 'permission') nên không ai mất quyền; ở đây chỉ:
 *  - chuyển mọi quyền stability_* sang nhóm quyền của Tồn Kho,
 *  - đổi tên hiển thị hai quyền xem thành tên tab,
 *  - PageTabs::seed() gắn hai quyền đó làm quyền con (parent_id) của inventory_standard_view.
 */
return new class extends Migration
{
    private const VIEWS = [
        'stability_standard_view' => ['Tab Đánh giá hạn dùng CTC', 'Xem Đánh Giá Chuẩn Thứ Cấp'],
        'stability_plan_view' => ['Tab Kế hoạch đánh giá', 'Xem Kế Hoạch Đánh Giá'],
    ];

    public function up(): void
    {
        $inventory = DB::table('permissions')->where('name', 'inventory_standard_view')->first();

        if (! $inventory) {
            return;
        }

        DB::table('permissions')->where('name', 'like', 'stability\_%')->update([
            'permission_group' => $inventory->permission_group,
            'updated_at' => now(),
        ]);

        foreach (self::VIEWS as $name => [$label]) {
            DB::table('permissions')->where('name', $name)->update(['display_name' => $label, 'updated_at' => now()]);
        }

        PageTabs::seed();
    }

    public function down(): void
    {
        $group = DB::table('permission_groups')->where('name', 'Đánh Giá Hạn Dùng')->value('id');

        DB::table('permissions')->where('name', 'like', 'stability\_%')->update([
            'permission_group' => $group,
            'parent_id' => null,
            'updated_at' => now(),
        ]);

        foreach (self::VIEWS as $name => [, $label]) {
            DB::table('permissions')->where('name', $name)->update(['display_name' => $label, 'updated_at' => now()]);
        }
    }
};
