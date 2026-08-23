<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - MẶT HÀNG TRONG PHIẾU DỰ TRÙ
 *
 * Mỗi dòng là một hoá chất được dự trù trong phiếu estimate_lists.
 *
 * category_id : -> chemical_categories.id, ĐƯỢC PHÉP NULL khi phòng ban dự trù một
 *               hoá chất chưa có trong Danh Mục. Lúc đó tên hoá chất do người lập
 *               phiếu tự gõ và lưu ở cột chem_name.
 * chem_name   : tên hoá chất tự nhập, chỉ dùng khi category_id = NULL. Có danh mục
 *               rồi thì để trống, tên lấy theo chemical_categories.
 *
 * Số lượng dự trù không nằm ở đây: một mặt hàng có thể được dự trù cho nhiều tháng
 * với số lượng khác nhau, xem estimate_item_amounts.
 *
 * Dòng mặt hàng chỉ được thêm/sửa/xoá khi phiếu còn ở trạng thái Nháp hoặc Bị từ chối.
 * Phiếu đã trình ký thì khoá toàn bộ chi tiết, xem ChemicalEstimateController::editable().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimate_list_id');             // -> estimate_lists.id
            $table->unsignedBigInteger('category_id')->nullable();      // -> chemical_categories.id (null = ngoài danh mục)
            $table->string('chem_name', 255)->nullable();               // Tên hoá chất tự nhập khi ngoài danh mục
            $table->text('technical_information')->nullable();          // Thông tin kỹ thuật / tiêu chuẩn yêu cầu
            $table->text('purpose')->nullable();                        // Mục đích sử dụng
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('estimate_list_id', 'estimate_items_estimate_list_id_index');
            $table->index('category_id', 'estimate_items_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
