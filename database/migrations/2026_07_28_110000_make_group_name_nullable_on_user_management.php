<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bỏ khái niệm "Tổ" (stage_groups) khỏi màn hình quản lý User: bảng
 * stage_groups không tồn tại trong DB của LMS (chỉ có ở dự án PMS), nên
 * cột groupName không còn được UserController ghi giá trị vào nữa.
 * Nới lỏng NOT NULL để không chặn tạo/sửa user.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('user_management', 'groupName')) {
            DB::statement('ALTER TABLE `user_management` MODIFY `groupName` VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_management', 'groupName')) {
            DB::statement("ALTER TABLE `user_management` MODIFY `groupName` VARCHAR(50) NOT NULL DEFAULT ''");
        }
    }
};
