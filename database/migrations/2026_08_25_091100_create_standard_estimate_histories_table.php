<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - NHẬT KÝ TRÌNH KÝ / TIẾP NHẬN PHIẾU DỰ TRÙ CHẤT CHUẨN
 *
 * Mỗi lần phiếu đổi trạng thái (trình ký, ký bước 1, ký bước 2, từ chối, tiếp nhận,
 * giải quyết xong) đều ghi một dòng ở đây để nút "Theo dõi trình ký" hiển thị đầy đủ
 * ai làm gì, lúc nào, ghi chú ra sao.
 *
 * Bảng chỉ ghi thêm, không sửa, không xoá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_estimate_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_estimate_id'); // -> standard_estimates.id
            $table->string('action', 50);                       // Trình ký / Ký duyệt / Từ chối / Tiếp nhận...
            $table->string('step', 30)->nullable();             // manager | director | reception
            $table->string('from_status', 30)->nullable();      // Trạng thái trước
            $table->string('to_status', 30)->nullable();        // Trạng thái sau
            $table->string('note', 500)->nullable();            // Ghi chú / lý do
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('standard_estimate_id', 'standard_estimate_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_estimate_histories');
    }
};
