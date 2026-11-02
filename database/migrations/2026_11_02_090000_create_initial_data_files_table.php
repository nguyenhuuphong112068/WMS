<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File khởi tạo dữ liệu đã điền mà các phòng ban đính kèm lên màn hình Khởi Tạo Dữ Liệu.
 *
 * CHỈ lưu file để Admin xem / tải về tổng hợp, làm sạch rồi tự nạp bằng lệnh wms:initial-data -
 * đính kèm KHÔNG ghi gì vào bảng nghiệp vụ. Mỗi dòng gắn giai đoạn / module / phòng ban, lần nộp
 * thứ mấy của bộ đó, người + giờ đính kèm, mã băm file (chặn nộp trùng) và trạng thái Admin xử lý.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initial_data_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('stage')->nullable()->comment('Giai đoạn 1-4, null = file đủ 4 giai đoạn');
            $table->string('module', 20)->nullable()->comment('material / chemical / standard, null = cả 3 module');
            $table->unsignedBigInteger('department_id')->nullable()->comment('Phòng ban của dữ liệu trong file, null = toàn hệ thống');
            $table->unsignedInteger('submission_no')->default(1)->comment('Lần nộp thứ mấy của cùng giai đoạn + module + phòng ban');
            $table->string('original_name');
            $table->string('stored_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_hash', 64)->comment('sha256 nội dung file - chặn nộp trùng');
            $table->string('template_version', 50)->nullable()->comment('Phiên bản file mẫu đọc từ file, null = không phải file mẫu WMS');
            $table->text('note')->nullable();
            $table->string('review_status', 20)->default('pending')->comment('pending / processed / returned');
            $table->text('review_note')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->unsignedBigInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['stage', 'module', 'department_id']);
            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initial_data_files');
    }
};
