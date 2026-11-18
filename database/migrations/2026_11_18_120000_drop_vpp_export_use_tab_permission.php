<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 bỏ tab "Sử Dụng VPP" của màn Sử Dụng VPP: nút [+ Sử dụng VPP] chuyển sang tab
 * "Sổ sử dụng VPP". Ai đang được xem tab Sử Dụng VPP mà chưa có tab Sổ thì được cấp thêm quyền
 * tab Sổ (vai trò + quyền riêng người dùng) để không mất nút, rồi xoá quyền export_vpp_tab_use.
 * down() chỉ tạo lại quyền, không gán lại.
 */
return new class extends Migration
{
    private const NAME = 'export_vpp_tab_use';

    private const BOOK = 'export_vpp_tab_book';

    private const BOOK_OLD = 'Xem tab Sổ sử dụng VPP: nhật ký mọi lần VPP rời kho của phòng (sử dụng, loại bỏ, chuyển cho phòng khác).';

    private const BOOK_NEW = "Xem tab Sổ sử dụng VPP: nhật ký mọi lần VPP rời kho của phòng (sử dụng, loại bỏ, chuyển cho phòng khác) và nút Sử dụng VPP để ghi phiếu (quét mã hoặc chọn lô từ tồn kho của phòng).\nLưu ý: ghi phiếu cần thêm quyền Sử Dụng VPP.";

    private function swapBook(string $from, string $to): void
    {
        DB::table('permissions')->where('name', self::BOOK)->where('description', $from)->update(['description' => $to]);
    }

    public function up(): void
    {
        $this->swapBook(self::BOOK_OLD, self::BOOK_NEW);

        $useId = DB::table('permissions')->where('name', self::NAME)->value('id');

        if (! $useId) {
            return;
        }

        $bookId = DB::table('permissions')->where('name', self::BOOK)->value('id');

        if ($bookId) {
            $roleIds = DB::table('role_permission')->where('permission_id', $useId)
                ->whereNotIn('role_id', DB::table('role_permission')->where('permission_id', $bookId)->select('role_id'))
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $bookId]);
            }

            if (Schema::hasTable('user_permission')) {
                $grants = DB::table('user_permission')->where('permission_id', $useId)->where('is_denied', 0)
                    ->whereNotIn('user_id', DB::table('user_permission')->where('permission_id', $bookId)->select('user_id'))
                    ->pluck('user_id');

                foreach ($grants as $userId) {
                    DB::table('user_permission')->insert(['user_id' => $userId, 'permission_id' => $bookId, 'is_denied' => 0]);
                }
            }
        }

        foreach (['role_permission', 'user_permission'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('permission_id', $useId)->delete();
            }
        }

        DB::table('permissions')->where('id', $useId)->delete();
    }

    public function down(): void
    {
        $this->swapBook(self::BOOK_NEW, self::BOOK_OLD);

        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            return;
        }

        $parent = DB::table('permissions')->where('name', 'export_vpp_view')->first();

        DB::table('permissions')->insert([
            'permission_group' => $parent->permission_group ?? null,
            'parent_id' => $parent->id ?? null,
            'name' => self::NAME,
            'display_name' => 'Tab Sử Dụng VPP',
            'description' => "Xem tab Sử Dụng VPP: nơi bấm Sử dụng VPP để ghi phiếu (quét mã hoặc chọn lô từ tồn kho của phòng).\nLưu ý: ghi phiếu cần thêm quyền Sử Dụng VPP.",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
