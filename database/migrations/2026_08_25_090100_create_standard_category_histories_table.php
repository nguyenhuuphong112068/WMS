<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - LỊCH SỬ THAY ĐỔI DANH MỤC CHẤT CHUẨN
 *
 * Mỗi lần Thêm mới / Cập nhật / Khoá / Duyệt một dòng ở standard_categories sẽ ghi
 * thêm một dòng ở đây, chụp lại giá trị của bản ghi NGAY SAU khi thay đổi.
 * Cột change_note mô tả cụ thể nội dung đã đổi theo dạng "Trường: cũ -> mới".
 *
 * Bảng chỉ ghi thêm, không sửa, không xoá - giống chemical_category_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_category_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_category_id');         // -> standard_categories.id
            $table->string('action', 30);                               // Thêm mới | Cập nhật | Khoá | Mở khoá | Phê duyệt | Từ chối duyệt

            // Ảnh chụp giá trị bản ghi sau khi thay đổi
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('chem_names_id')->nullable();
            $table->string('cas_no', 50)->nullable();
            $table->unsignedBigInteger('manufacturers_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('storage_condition_id')->nullable();
            $table->unsignedInteger('version')->nullable();
            $table->string('groups', 255)->nullable();
            $table->unsignedSmallInteger('shelf_life_months')->nullable();
            $table->string('doc_no', 20)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('app_status', 20)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                    // Mô tả nội dung đã đổi
            $table->string('created_by')->nullable();                   // Người thực hiện
            $table->timestamp('created_at')->nullable();                // Thời điểm thực hiện

            $table->index('standard_category_id', 'standard_category_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_category_histories');
    }
};
