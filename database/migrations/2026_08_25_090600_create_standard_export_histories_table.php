<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - LỊCH SỬ ĐIỀU CHỈNH PHIẾU SỬ DỤNG CHẤT CHUẨN
 *
 * Mỗi lần Thêm mới / Cập nhật / Khoá / Mở khoá một dòng ở standard_exports sẽ ghi
 * thêm một dòng ở đây, chụp lại giá trị của phiếu NGAY SAU khi thay đổi.
 *
 * Phiếu sử dụng chất chuẩn trừ thẳng vào tồn và là căn cứ truy ngược "phép thử này
 * dùng ống chuẩn nào" nên phải giữ được vết từng lần chỉnh.
 *
 * change_note : mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới", nối bằng " | ".
 *               Người dùng nhập thêm lý do điều chỉnh thì lý do đứng đầu chuỗi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_export_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_export_id');        // -> standard_exports.id
            $table->string('action', 30);                            // Thêm mới | Cập nhật | Khoá | Mở khoá

            // Ảnh chụp giá trị của phiếu sau khi thay đổi
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->string('type', 20)->nullable();                  // export | cancel
            $table->date('exported_date')->nullable();
            $table->string('exported_by', 255)->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('test_report_no', 100)->nullable();
            $table->string('checked_by', 255)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                 // Nội dung đã đổi + lý do điều chỉnh
            $table->string('created_by')->nullable();                // Người thực hiện
            $table->timestamp('created_at')->nullable();             // Thời điểm thực hiện

            $table->index('standard_export_id', 'standard_export_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_export_histories');
    }
};
