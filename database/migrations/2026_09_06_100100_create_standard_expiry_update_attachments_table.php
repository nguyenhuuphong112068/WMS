<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File đính kèm cho từng lần cập nhật hạn dùng (standard_expiry_updates).
 *
 * File gốc lưu ở disk mặc định (storage/app/private/public/standard_expiry_updates/...),
 * route downloadExpiryAttachment đọc từ đó; AttachmentBackup còn tạo thêm một bản sao
 * ở public/uploads/standard_expiry_updates/ theo yêu cầu quản trị.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_expiry_update_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_expiry_update_id');     // -> standard_expiry_updates.id
            $table->string('file_name');                                 // Tên file hiển thị (original name)
            $table->string('file_path');                                 // Đường dẫn lưu trữ (relative storage path)
            $table->unsignedBigInteger('file_size')->nullable();         // Dung lượng (bytes)
            $table->string('file_type', 100)->nullable();                // MIME type hoặc extension
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('standard_expiry_update_id', 'std_exp_upd_att_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_expiry_update_attachments');
    }
};
