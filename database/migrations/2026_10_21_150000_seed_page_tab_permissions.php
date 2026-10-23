<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền xem từng tab cho mọi trang nhiều tab (App\Support\PageTabs::PAGES) - quyền con của quyền
 * xem trang. Quyền tab mới được cấp cho mọi nhóm quyền đang xem được trang (không ai mất tab đang
 * dùng). Quyền tab đã có (Dự Trù Vật Tư - 2026_10_21_140000) và export_chemical_disposal_view
 * (tab "Hoá chất chờ huỷ") chỉ được gắn quyền cha, giữ nguyên phân quyền.
 *
 * "Nhân Viên Cung Ứng Vật Tư" ở Dự Trù Hoá Chất / Chất Chuẩn chỉ có các tab của bộ phận mua
 * hàng như Dự Trù Vật Tư.
 */
return new class extends Migration
{
    private const SKIP = [
        'Nhân Viên Cung Ứng Vật Tư' => [
            'estimate_chemical' => ['list'],
            'estimate_standard' => ['list'],
        ],
    ];

    public function up(): void
    {
        PageTabs::seed(self::SKIP);
    }

    public function down(): void
    {
        $names = [];
        foreach (PageTabs::PAGES as $page => $config) {
            if ($page === 'estimate_material') {
                continue; // thuộc migration 2026_10_21_140000
            }
            foreach (array_keys($config['tabs']) as $tab) {
                $names[] = PageTabs::permission($page, $tab);
            }
        }

        DB::table('permissions')->where('name', 'export_chemical_disposal_view')->update(['parent_id' => null]);

        $ids = DB::table('permissions')->whereIn('name', array_diff($names, ['export_chemical_disposal_view']))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
