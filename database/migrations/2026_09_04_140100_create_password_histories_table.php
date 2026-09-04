<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21 CFR Part 11 §11.300(b) - không cho dùng lại mật khẩu cũ.
 *
 * Mỗi lần đặt / đổi mật khẩu ghi thêm một dòng hash vào đây. Khi đổi mật khẩu,
 * so mật khẩu mới với N hash gần nhất (kể cả hash hiện tại) - trùng thì từ chối.
 * Bảng chỉ ghi thêm, không sửa, không xoá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('password_hash');
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id', 'password_histories_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
