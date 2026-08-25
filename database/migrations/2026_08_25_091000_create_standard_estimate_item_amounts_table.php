<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - SỐ LƯỢNG DỰ TRÙ CHẤT CHUẨN THEO THÁNG
 *
 * Một mặt hàng trong phiếu dự trù (standard_estimate_items) có thể cần dùng rải ra
 * nhiều tháng, mỗi tháng một số lượng. Mỗi dòng ở đây là số lượng cần cho ĐÚNG MỘT tháng.
 *
 * unit_id       : -> units.id. Đơn vị lưu riêng ở đây (không lấy theo danh mục) vì chất
 *                 chuẩn ngoài danh mục chưa có đơn vị gốc, và phòng ban có thể dự trù
 *                 theo quy cách đóng gói (ống, lọ) khác đơn vị tồn kho (mg, ml).
 * for_month_year: tháng cần dùng, lưu dạng DATE ngày đầu tháng; hiển thị dạng MM/YYYY.
 *
 * Các dòng số lượng được ghi lại toàn bộ mỗi lần lưu mặt hàng (xoá cũ - ghi mới).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_estimate_item_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_estimate_item_id'); // -> standard_estimate_items.id
            $table->decimal('amount', 15, 4)->default(0);            // Số lượng dự trù cho tháng đó
            $table->unsignedBigInteger('unit_id')->nullable();       // -> units.id
            $table->date('for_month_year');                          // Tháng cần dùng (ngày đầu tháng)
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('standard_estimate_item_id', 'standard_estimate_amounts_item_index');
            $table->index('for_month_year', 'standard_estimate_amounts_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_estimate_item_amounts');
    }
};
