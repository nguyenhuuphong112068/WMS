<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiêu đề của đề nghị cấp phát vật tư, do Tổ tự đặt khi lập phiếu ("Đề nghị vật tư bảo trì
 * tháng 9"...). Khác với note: name là tên gọi để tra cứu trên danh sách, note là ghi chú
 * dài cuối phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('material_request_lists', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
