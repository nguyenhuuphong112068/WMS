<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 bỏ tab "Khả dụng tháng tới" của Tồn Kho VPP (VPP không còn danh sách đề nghị nội bộ
 * theo chu kỳ nên tab gần như luôn trống): xoá quyền xem tab inventory_vpp_tab_next cùng mọi
 * gán quyền của vai trò / người dùng. down() chỉ tạo lại quyền, không gán lại cho vai trò.
 */
return new class extends Migration
{
    private const NAME = 'inventory_vpp_tab_next';

    /** Diễn giải tab Danh sách VPP cần dự trù: bỏ nguồn "thiếu ở Khả dụng tháng tới". */
    private const WATCHLIST_OLD = 'do người dùng đề nghị, hoặc thiếu ở Khả dụng tháng tới;';

    private const WATCHLIST_NEW = 'hoặc do người dùng đề nghị;';

    public function up(): void
    {
        $this->swapWatchlist(self::WATCHLIST_OLD, self::WATCHLIST_NEW);

        $ids = DB::table('permissions')->where('name', self::NAME)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        foreach (['role_permission', 'user_permission'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('permission_id', $ids)->delete();
            }
        }

        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function swapWatchlist(string $from, string $to): void
    {
        $row = DB::table('permissions')->where('name', 'estimate_vpp_tab_watchlist')->first();

        if ($row && str_contains((string) $row->description, $from)) {
            DB::table('permissions')->where('id', $row->id)->update([
                'description' => str_replace($from, $to, $row->description),
            ]);
        }
    }

    public function down(): void
    {
        $this->swapWatchlist(self::WATCHLIST_NEW, self::WATCHLIST_OLD);

        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            return;
        }

        $parentId = DB::table('permissions')->where('name', 'inventory_vpp_view')->value('id');

        DB::table('permissions')->insert([
            'permission_group' => DB::table('permissions')->where('id', $parentId)->value('permission_group'),
            'parent_id' => $parentId,
            'name' => self::NAME,
            'display_name' => 'Tab Khả dụng tháng tới',
            'description' => 'Xem tab Khả dụng tháng tới: tồn hiện có có đủ cho nhu cầu theo chu kỳ của tháng tới không, thiếu bao nhiêu, lượng nên dự trù.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
