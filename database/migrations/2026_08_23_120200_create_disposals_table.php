<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỢT HUỶ HOÁ CHẤT (PHIẾU THEO DÕI VÀ QUYẾT ĐỊNH HUỶ)
 *
 * Mỗi bản ghi là MỘT lần xin quyết định huỷ, gom nhiều phiếu loại bỏ
 * (exports.type = 'cancel', exports.disposal_id = disposals.id) để trình một lần
 * thay vì xin từng phiếu. Cấu trúc cột bám đúng biểu mẫu QA/F/058-07 để in ra được:
 *
 * Phần đầu   : code (số phiếu theo dõi), period_month / period_year (Tháng/ năm),
 *              department_id (Bộ phận giao phế phẩm), decision_no (Quyết định số).
 * Mục 1      : summarized_by / summarized_at (Người tổng kết, Ngày),
 *              chemical_staff (Nhân Viên Quản Lý Hoá Chất), checked_at (Ngày kiểm tra).
 *              Danh sách phế phẩm nằm ở bảng exports, không nhân bản lại tại đây.
 * Mục 2      : other_note, method, planned_time, executor_type / executor_other,
 *              qa_approved_* (TP. ĐBCL), director_approved_* (Ban Giám Đốc).
 * Mục 3      : solid_weight / liquid_weight (tổng khối lượng phế phẩm rắn / lỏng, kg),
 *              handover_* (người giao), receive_* (người nhận - Hành chánh).
 * Mục 4      : label_* (kiểm tra, dán nhãn "Chấp nhận huỷ"), destroy_* (tiến hành huỷ).
 *
 * app_status - vòng đời của một đợt:
 *   draft    : đang gom phiếu, còn thêm bớt và sửa được.
 *   pending  : đã trình TP. ĐBCL và Ban Giám Đốc, khoá danh sách phiếu lại.
 *   approved : đã có quyết định huỷ -> IN được biểu mẫu.
 *   rejected : không được duyệt, các phiếu được thả về hàng chờ để gom đợt khác.
 *   done     : đã huỷ xong, đủ chữ ký giao nhận và theo dõi huỷ ở mục 3, mục 4.
 *
 * status_id : 1 = hiệu lực, 0 = đã khoá. Không xoá cứng - đây là hồ sơ chất lượng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                    // Số phiếu theo dõi: HUY-YYYYMM-NN
            $table->unsignedBigInteger('department_id');             // -> deparments.id, bộ phận giao phế phẩm
            $table->unsignedTinyInteger('period_month');             // Tháng của đợt huỷ
            $table->unsignedSmallInteger('period_year');             // Năm của đợt huỷ

            // ----- Mục 1: Tổng kết phế phẩm -----
            $table->string('summarized_by', 255)->nullable();        // Người tổng kết
            $table->date('summarized_at')->nullable();               // Ngày tổng kết
            $table->string('chemical_staff', 255)->nullable();       // Nhân viên quản lý hoá chất
            $table->date('checked_at')->nullable();                  // Ngày kiểm tra

            // ----- Mục 2: Quyết định huỷ bỏ -----
            $table->enum('app_status', ['draft', 'pending', 'approved', 'rejected', 'done'])->default('draft');
            $table->string('decision_no', 100)->nullable();          // Quyết định số
            $table->string('other_note', 500)->nullable();           // Ghi chú khác
            $table->enum('method', ['burn', 'dissolve'])->nullable(); // 1. Đốt | 2. Hoà tan trong nước
            $table->string('planned_time', 255)->nullable();         // Thời gian dự kiến thực hiện
            $table->enum('executor_type', ['agency', 'other'])->nullable(); // Cơ quan huỷ | đơn vị khác
            $table->string('executor_other', 255)->nullable();       // Tên đơn vị khi chọn "khác"
            $table->string('qa_approved_by', 255)->nullable();       // TP. ĐBCL
            $table->date('qa_approved_at')->nullable();
            $table->string('director_approved_by', 255)->nullable(); // Ban Giám Đốc
            $table->date('director_approved_at')->nullable();
            $table->string('reject_reason', 500)->nullable();        // Lý do không duyệt
            $table->dateTime('submitted_at')->nullable();            // Lúc trình duyệt
            $table->string('submitted_by', 255)->nullable();

            // ----- Mục 3: Giao nhận phế phẩm -----
            $table->decimal('solid_weight', 15, 4)->nullable();      // Tổng khối lượng phế phẩm rắn (kg)
            $table->decimal('liquid_weight', 15, 4)->nullable();     // Tổng khối lượng phế phẩm lỏng (kg)
            $table->date('handover_date')->nullable();
            $table->string('handover_by', 255)->nullable();          // Người giao
            $table->date('receive_date')->nullable();
            $table->string('receive_by', 255)->nullable();           // Người nhận (Hành chánh)

            // ----- Mục 4: ĐBCL kiểm tra và theo dõi huỷ -----
            $table->date('label_date')->nullable();                  // Kiểm tra, dán nhãn "Chấp nhận huỷ"
            $table->string('label_by', 255)->nullable();
            $table->date('destroy_date')->nullable();                // Tiến hành huỷ
            $table->string('destroy_by', 255)->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'disposals_department_id_index');
            $table->index('app_status', 'disposals_app_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposals');
    }
};
