<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - PHIẾU DỰ TRÙ CHẤT CHUẨN (phần đầu phiếu)
 *
 * Mỗi dòng là một phiếu dự trù chất chuẩn của một phòng ban cho một tháng/năm.
 * Chi tiết từng chất chuẩn nằm ở standard_estimate_items, số lượng theo từng tháng
 * nằm ở standard_estimate_item_amounts.
 *
 * code : mã phiếu sinh tự động DTC + department_id + năm + tháng(2) + số thứ tự 3 chữ số.
 *        Ví dụ phòng ban id = 2, dự trù tháng 9/2026, phiếu đầu tiên -> DTC2202609001.
 *        Tiền tố DTC tách khỏi DT của dự trù hoá chất để nhìn mã là biết loại phiếu.
 *
 * TRÌNH KÝ (app_status) dùng chung khai báo tại config/estimate.php với dự trù hoá chất:
 *   draft -> pending_manager -> pending_director -> approved
 *   Bị từ chối ở bước nào cũng đưa về rejected, sửa lại rồi trình ký lại từ đầu.
 *
 * TIẾP NHẬN (reception_status): chỉ có giá trị sau khi phiếu được phê duyệt,
 *   do bộ phận Cung Ứng cập nhật: waiting -> received -> completed.
 *
 * status_id : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá). Không xoá cứng phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_estimates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                   // Mã phiếu dự trù (sinh tự động)
            $table->unsignedBigInteger('department_id');            // -> deparments.id
            $table->tinyInteger('month');                           // Tháng dự trù (1..12)
            $table->smallInteger('year');                           // Năm dự trù
            $table->string('note', 500)->nullable();                // Ghi chú chung của phiếu

            // ---------- Trình ký ----------
            $table->string('app_status', 30)->default('draft');
            $table->string('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('manager_signed_by')->nullable();        // Bước 1: Phó/Trưởng Phòng
            $table->timestamp('manager_signed_at')->nullable();
            $table->string('director_signed_by')->nullable();       // Bước 2: Ban Giám Đốc
            $table->timestamp('director_signed_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('reject_step', 30)->nullable();          // manager | director
            $table->string('reject_reason', 500)->nullable();

            // ---------- Tiếp nhận của bộ phận Cung Ứng ----------
            $table->string('reception_status', 30)->nullable();     // waiting|received|completed
            $table->string('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('reception_note', 500)->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'standard_estimates_department_id_index');
            $table->index('app_status', 'standard_estimates_app_status_index');
            $table->index(['year', 'month'], 'standard_estimates_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_estimates');
    }
};
