<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - MẶT HÀNG TRONG PHIẾU DỰ TRÙ CHẤT CHUẨN
 *
 * category_id : -> standard_categories.id, ĐƯỢC PHÉP NULL khi phòng ban dự trù một
 *               chất chuẩn chưa có trong Danh Mục. Lúc đó tên do người lập phiếu tự
 *               gõ và lưu ở cột standard_name.
 * standard_name : tên chất chuẩn tự nhập, chỉ dùng khi category_id = NULL.
 * group_key   : nhóm chuẩn mong muốn, lưu KHOÁ của config('standard.groups') (PRS,
 *               IMPRS...) chứ không phải mã đưa vào mã ống chuẩn - dự trù chưa có ống
 *               nào nên chưa cần tới mã. Để bộ phận Cung Ứng biết cần mua chuẩn chính
 *               hay chuẩn tạp; bắt buộc khi khai chất chuẩn ngoài danh mục.
 *
 * Số lượng dự trù không nằm ở đây: một mặt hàng có thể được dự trù cho nhiều tháng
 * với số lượng khác nhau, xem standard_estimate_item_amounts.
 *
 * Dòng mặt hàng chỉ được thêm/sửa/xoá khi phiếu còn Nháp hoặc Bị từ chối.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_estimate_id');         // -> standard_estimates.id
            $table->unsignedBigInteger('category_id')->nullable();      // -> standard_categories.id
            $table->string('standard_name', 255)->nullable();           // Tên tự nhập khi ngoài danh mục
            $table->string('group_key', 10)->nullable();                 // Khoá nhóm chuẩn mong muốn
            $table->text('technical_information')->nullable();          // Thông tin kỹ thuật / tiêu chuẩn yêu cầu
            $table->text('purpose')->nullable();                        // Mục đích sử dụng
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('standard_estimate_id', 'standard_estimate_items_parent_index');
            $table->index('category_id', 'standard_estimate_items_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_estimate_items');
    }
};
