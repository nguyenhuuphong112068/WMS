<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lịch sử đăng nhập (theo dự án PMS) — mỗi lần thử đăng nhập là một dòng (thành công, sai mật khẩu, bị khoá…).
     * Dòng thành công được cập nhật thêm thời điểm thao tác cuối / đăng xuất, nên xem được thời lượng phiên.
     * Bảng chỉ ghi thêm, không có màn hình sửa / xoá. user_id = user_management.id.
     */
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('userName', 60)->index()->comment('Tên đăng nhập đã nhập (kể cả không tồn tại)');
            $table->string('status', 20)->index()->comment('success | failed | locked | disabled | pwchange');
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('platform', 60)->nullable();
            $table->string('session_id', 100)->nullable()->index();
            $table->timestamp('login_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->comment('Lần thao tác gần nhất trong phiên');
            $table->timestamp('logout_at')->nullable();
            $table->string('logout_type', 20)->nullable()->comment('manual | timeout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
