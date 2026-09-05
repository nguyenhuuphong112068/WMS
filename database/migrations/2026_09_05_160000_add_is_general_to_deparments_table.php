<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phòng ban chung (VD: BOD - Ban Giám Đốc, PROC - Cung Ứng) chỉ dùng để tạo user cho
 * người ký duyệt / tiếp nhận dự trù (theo vai trò khai ở config/estimate.php), không có
 * kho hàng riêng - is_general = 0 để ẩn khỏi Droplist Chuyển Bộ Phận trên leftNAV và
 * các màn tra cứu tồn kho theo phòng ban.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deparments') && ! Schema::hasColumn('deparments', 'is_general')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->boolean('is_general')->default(true)->after('isActive');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deparments') && Schema::hasColumn('deparments', 'is_general')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->dropColumn('is_general');
            });
        }
    }
};
