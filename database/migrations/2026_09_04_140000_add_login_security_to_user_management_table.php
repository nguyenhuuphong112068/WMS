<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21 CFR Part 11 §11.300(d) - chống truy cập trái phép:
 *   - failed_login_attempts : số lần nhập sai mật khẩu liên tiếp
 *   - locked_until          : thời điểm hết khoá (khoá 15 phút sau 5 lần sai, tự mở)
 *
 * §11.300(b) - vòng đời mật khẩu: cột changePWdate đã có sẵn (hạn đổi mật khẩu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (! Schema::hasColumn('user_management', 'failed_login_attempts')) {
                $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('isActive');
            }
            if (! Schema::hasColumn('user_management', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_management', function (Blueprint $table) {
            if (Schema::hasColumn('user_management', 'locked_until')) {
                $table->dropColumn('locked_until');
            }
            if (Schema::hasColumn('user_management', 'failed_login_attempts')) {
                $table->dropColumn('failed_login_attempts');
            }
        });
    }
};
