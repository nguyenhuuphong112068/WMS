<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TỒN - ĐÁNH GIÁ HẠN DÙNG CHẤT CHUẨN
 *
 * Một ống chuẩn đang tồn (standard_imports) được lập MỘT PHIẾU ĐÁNH GIÁ HẠN DÙNG để
 * theo dõi độ ổn định của chất chuẩn theo thời gian:
 *
 *      standard_stability_assessment_list  : đầu phiếu - đánh giá cho ống chuẩn nào,
 *                                            bắt đầu từ ngày nào, chu kỳ bao nhiêu tháng.
 *      standard_stability_assessment_item  : từng MỐC ĐÁNH GIÁ của phiếu (T0, T3, T6...),
 *                                            mỗi mốc có ngày đến hạn, các chỉ tiêu cần
 *                                            thử nghiệm, ngày thực hiện và kết quả.
 *
 * due_date = start_date + timepoint tháng, tính sẵn khi ghi để bảng và bộ lọc "đến hạn"
 * chỉ so sánh trên một cột thay vì cộng ngày ở mọi câu truy vấn.
 *
 * testings lưu MẢNG chỉ tiêu thử nghiệm dạng JSON (["Định tính","Định lượng"]) vì số
 * chỉ tiêu mỗi mốc mỗi khác, tách thành bảng con thì nặng hơn giá trị nó mang lại.
 *
 * status là chuỗi tiếng Việt hiển thị thẳng lên màn hình, mặc định "Ban Đầu":
 *      Phiếu : Ban Đầu -> Đang Đánh Giá -> Hoàn Thành (hoặc Huỷ).
 *      Mốc   : Ban Đầu -> Đạt / Không Đạt.
 * Phiếu không xoá cứng, chỉ chuyển trạng thái "Huỷ".
 *
 * Chú ý: tên cột khoá ngoại standard_stability_assessment_list_id rất dài nên MỌI index
 * đều phải đặt tên tay, để Laravel tự sinh sẽ vượt giới hạn 64 ký tự của MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_stability_assessment_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');                    // -> standard_imports.id
            $table->date('start_date');                                 // Ngày bắt đầu đánh giá
            $table->string('status', 50)->default('Ban Đầu');           // Ban Đầu / Đang Đánh Giá / Hoàn Thành / Huỷ
            $table->tinyInteger('assessment_period');                   // Chu kỳ đánh giá (tháng)
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();

            $table->index('import_id', 'ssa_list_import_id_index');
            $table->index('status', 'ssa_list_status_index');
        });

        Schema::create('standard_stability_assessment_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_stability_assessment_list_id'); // -> standard_stability_assessment_list.id
            $table->string('name', 100);                                // Tên mốc đánh giá (Ban đầu, Tháng 3...)
            $table->tinyInteger('timepoint');                           // Mốc thời gian tính từ ngày bắt đầu (tháng)
            $table->date('due_date')->nullable();                       // Ngày đến hạn = start_date + timepoint tháng
            $table->string('testings', 500)->nullable();                // Chỉ tiêu thử nghiệm, mảng JSON
            $table->boolean('issured')->default(0);                     // Đã phát hành phiếu thử nghiệm cho mốc này
            $table->dateTime('done_at')->nullable();                    // Thời điểm thực hiện đánh giá
            $table->string('result', 255)->nullable();                  // Kết quả đánh giá
            $table->string('status', 50)->default('Ban Đầu');           // Ban Đầu / Đạt / Không Đạt
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('updated_by')->nullable();

            $table->index('standard_stability_assessment_list_id', 'ssa_item_list_id_index');
            $table->index('due_date', 'ssa_item_due_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_stability_assessment_item');
        Schema::dropIfExists('standard_stability_assessment_list');
    }
};
