<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 phiếu mua hàng VPP bỏ nút Trao đổi từng mặt hàng: xoá quyền estimate_vpp_chat
 * (gỡ khỏi vai trò + quyền riêng người dùng). Tin trao đổi cũ trong estimate_item_chats giữ nguyên.
 * down() chỉ tạo lại quyền, không gán lại.
 */
return new class extends Migration
{
    private const NAME = 'estimate_vpp_chat';

    public function up(): void
    {
        $id = DB::table('permissions')->where('name', self::NAME)->value('id');

        if (! $id) {
            return;
        }

        foreach (['role_permission', 'user_permission'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('permission_id', $id)->delete();
            }
        }

        DB::table('permissions')->where('id', $id)->delete();
    }

    public function down(): void
    {
        if (DB::table('permissions')->where('name', self::NAME)->exists()) {
            return;
        }

        $parent = DB::table('permissions')->where('name', 'estimate_vpp_view')->first();

        DB::table('permissions')->insert([
            'permission_group' => $parent->permission_group ?? null,
            'parent_id' => $parent->id ?? null,
            'name' => self::NAME,
            'display_name' => 'Trao Đổi Theo Dõi Mua Hàng',
            'description' => 'Gửi tin nhắn trao đổi ở nút Trao đổi của từng VPP trong chi tiết phiếu mua hàng VPP đã duyệt.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
