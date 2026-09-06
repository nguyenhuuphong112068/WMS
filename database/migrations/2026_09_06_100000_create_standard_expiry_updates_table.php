<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TỒN - LỊCH SỬ CẬP NHẬT HẠN DÙNG CỦA MỘT ỐNG CHUẨN
 *
 * Mỗi lần bấm badge "Retest" / "Check online" trên màn hình Tồn Kho Chất Chuẩn để
 * cập nhật lại hạn dùng (tiếp tục retest / tiếp tục check online / chốt hạn dùng xác
 * định) sẽ ghi thêm một dòng ở đây, chụp lại giá trị TRƯỚC và SAU khi đổi.
 *
 * - change_note : mô tả cụ thể nội dung đã đổi ("Loại hạn dùng: A -> B; Hạn dùng: x -> y")
 * - note        : lý do / ghi chú do người dùng nhập, không bắt buộc
 *
 * Bảng chỉ ghi thêm, không sửa, không xoá. File chứng minh (CoA, ảnh tra cứu) đính
 * kèm ở standard_expiry_update_attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_expiry_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_import_id');            // -> standard_imports.id
            $table->unsignedBigInteger('department_id');                 // -> deparments.id

            $table->string('old_expiry_type', 30)->nullable();           // retest | check online | Specify
            $table->string('new_expiry_type', 30)->nullable();
            $table->date('old_expired_date')->nullable();
            $table->date('new_expired_date')->nullable();
            $table->smallInteger('retest_interval_months')->nullable();  // Chu kỳ retest tại thời điểm cập nhật

            $table->text('change_note')->nullable();                     // Nội dung đã đổi
            $table->string('note', 500)->nullable();                     // Lý do / ghi chú người dùng nhập
            $table->string('created_by')->nullable();                    // Người cập nhật
            $table->timestamp('created_at')->nullable();                 // Thời điểm cập nhật

            $table->index('standard_import_id', 'standard_expiry_updates_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_expiry_updates');
    }
};
