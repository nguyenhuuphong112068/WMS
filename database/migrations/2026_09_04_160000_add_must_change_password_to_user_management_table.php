<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21 CFR Part 11 §11.300(b) - buộc đổi mật khẩu ở lần đăng nhập đầu tiên.
 *
 *   must_change_password = 1 : tài khoản đang dùng mật khẩu do quản trị đặt
 *                              (mới tạo / vừa được reset) -> đăng nhập xong
 *                              phải đổi mật khẩu mới được vào hệ thống.
 *   Người dùng tự đổi mật khẩu thành công thì cờ này về 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (! Schema::hasColumn('user_management', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('changePWdate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (Schema::hasColumn('user_management', 'must_change_password')) {
                $table->dropColumn('must_change_password');
            }
        });
    }
};
