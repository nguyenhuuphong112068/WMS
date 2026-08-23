<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - LỊCH SỬ ĐIỀU CHỈNH GHI THÊM SỐ PHIẾU KN / OOS / BCSL
 *
 * exports.test_report_no là căn cứ loại bỏ được in vào hồ sơ xin quyết định huỷ,
 * nên mọi lần đổi giá trị này phải truy lại được. Ảnh chụp lịch sử vì thế phải có
 * đủ cột giống bảng exports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_histories', function (Blueprint $table) {
            $table->string('test_report_no', 100)->nullable()->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('export_histories', function (Blueprint $table) {
            $table->dropColumn('test_report_no');
        });
    }
};
