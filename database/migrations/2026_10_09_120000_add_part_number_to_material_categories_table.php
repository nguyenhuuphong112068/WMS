<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PART NUMBER CỦA DANH MỤC VẬT TƯ CÔNG TY
 *
 * Mã linh kiện do nhà sản xuất đặt (VD bạc đạn 6302-2Z) - thuộc bản chất vật tư nên khai ở
 * danh mục chung, không bắt buộc. Bảng lịch sử thêm cùng cột để chụp lại mỗi lần thay đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->string('part_number', 100)->nullable()->after('manufacturers_id');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->string('part_number', 100)->nullable()->after('manufacturers_id');
        });
    }

    public function down(): void
    {
        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn('part_number');
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn('part_number');
        });
    }
};
