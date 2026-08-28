<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TÁCH DANH MỤC VẬT TƯ CÔNG TY KHỎI PHẦN CÁCH DÙNG CỦA PHÒNG
 *
 * Song song với hai migration cùng ngày bên hoá chất / chất chuẩn
 * (move_unit_id_to_department_chemicals / _standards).
 *
 * material_categories từ nay chỉ giữ "vật tư là gì": tên vật tư, nhà sản xuất, thông tin
 * kỹ thuật. Ba cột dưới đây là CÁCH DÙNG của từng phòng, chuyển sang department_materials:
 *
 *   - classification : phân loại A / B / C cứng -> department_materials.classification_id
 *   - min_stock      : ngưỡng tồn của phòng     -> department_materials.min_stock
 *   - unit_id        : đơn vị nhập/xuất của phòng -> department_materials.unit_id
 *
 * Cột unit_id trên material_category_histories được GIỮ LẠI để không mất ảnh chụp của các
 * lần thay đổi đã xảy ra, nhưng từ nay không ghi thêm giá trị nào vào đó nữa. Hai cột
 * classification / min_stock trên bảng lịch sử mới thêm hôm nay, chưa có ảnh chụp thật
 * nên bỏ hẳn.
 *
 * Bộ khoá chống trùng đổi từ (tên + NSX + đơn vị) thành (tên + NSX): một tổ hợp tên vật
 * tư + nhà sản xuất giờ chỉ có đúng một dòng danh mục công ty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropUnique('material_categories_combo_unique');
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn(['classification', 'min_stock', 'unit_id']);
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->unique(['material_names_id', 'manufacturers_id'], 'material_categories_combo_unique');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn(['classification', 'min_stock']);
        });
    }

    public function down(): void
    {
        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->string('classification', 10)->nullable();
            $table->double('min_stock', 15, 4)->nullable();
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropUnique('material_categories_combo_unique');
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->string('classification', 10)->nullable();
            $table->double('min_stock', 15, 4)->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->unique(['material_names_id', 'manufacturers_id', 'unit_id'], 'material_categories_combo_unique');
        });
    }
};
