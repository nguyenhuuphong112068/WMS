<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - LỊCH SỬ THAY ĐỔI DANH MỤC VẬT TƯ
 *
 * Mỗi lần Thêm mới / Cập nhật / Khoá / Duyệt một dòng ở material_categories sẽ ghi thêm
 * một dòng ở đây, chụp lại giá trị của bản ghi NGAY SAU khi thay đổi.
 * Cột change_note mô tả cụ thể nội dung đã đổi theo dạng "Trường: cũ -> mới".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_category_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_category_id');         // -> material_categories.id
            $table->string('action', 30);                               // Thêm mới | Cập nhật | Khoá | Mở khoá | Phê duyệt | Từ chối duyệt

            // Ảnh chụp giá trị bản ghi sau khi thay đổi
            $table->unsignedBigInteger('material_names_id')->nullable();
            $table->unsignedBigInteger('manufacturers_id')->nullable();
            $table->unsignedBigInteger('suppliers_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('app_status', 20)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                    // Mô tả nội dung đã đổi
            $table->string('created_by')->nullable();                   // Người thực hiện
            $table->timestamp('created_at')->nullable();                // Thời điểm thực hiện

            $table->index('material_category_id', 'material_category_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_category_histories');
    }
};
