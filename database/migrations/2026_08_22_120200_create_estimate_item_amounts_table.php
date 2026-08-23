<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - SỐ LƯỢNG DỰ TRÙ THEO THÁNG
 *
 * Một mặt hàng trong phiếu dự trù (estimate_items) có thể cần dùng rải ra nhiều tháng,
 * mỗi tháng một số lượng. Mỗi dòng ở đây là số lượng cần cho ĐÚNG MỘT tháng.
 *
 * unit_id       : -> units.id. Đơn vị lưu riêng ở đây (không lấy theo danh mục) vì hoá
 *                 chất ngoài danh mục chưa có đơn vị gốc, và phòng ban có thể dự trù
 *                 theo đơn vị đóng gói khác đơn vị tồn kho.
 * for_month_year: tháng cần dùng, lưu dạng DATE ngày đầu tháng (ví dụ 2026-09-01 = tháng 9/2026)
 *                 để sắp xếp và lọc theo mốc thời gian; hiển thị dạng MM/YYYY.
 *
 * Các dòng số lượng được ghi lại toàn bộ mỗi lần lưu mặt hàng (xoá cũ - ghi mới) và chỉ
 * thao tác được khi phiếu còn Nháp hoặc Bị từ chối.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_item_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimate_item_id');         // -> estimate_items.id
            $table->decimal('amount', 15, 4)->default(0);           // Số lượng dự trù cho tháng đó
            $table->unsignedBigInteger('unit_id')->nullable();      // -> units.id (đơn vị của số lượng)
            $table->date('for_month_year');                         // Tháng cần dùng (ngày đầu tháng)
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('estimate_item_id', 'estimate_item_amounts_item_id_index');
            $table->index('for_month_year', 'estimate_item_amounts_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_item_amounts');
    }
};
