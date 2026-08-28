<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐÁNH GIÁ HẠN DÙNG - NHẬT KÝ THAY ĐỔI CỦA MỘT PHIẾU
 *
 * Mọi thay đổi trên phiếu đánh giá hạn dùng đều ghi một dòng ở đây, gắn với
 * standard_stability_assessment_list.id để mở phiếu ra là xem lại được đầy đủ:
 * ai thêm mốc nào, sửa gì thành gì, xoá mốc nào, ghi kết quả ra sao.
 *
 * item_id chỉ có giá trị khi thay đổi nằm ở một mốc cụ thể; thay đổi đầu phiếu
 * (lập phiếu, sửa ngày bắt đầu / chu kỳ, huỷ, mở lại) để trống.
 *
 * Mốc bị xoá thì dòng nhật ký vẫn còn nhưng item_id trỏ tới bản ghi không còn tồn tại -
 * vì vậy target lưu sẵn tên mốc dạng chữ, đọc lại không cần join.
 *
 * Bảng CHỈ GHI THÊM, không sửa, không xoá. Song song với Audit Trail chung: Audit Trail
 * để truy vết toàn hệ thống, bảng này để người dùng xem ngay trên màn hình phiếu.
 *
 * Chú ý: tên cột khoá ngoại rất dài nên index phải đặt tên tay, để Laravel tự sinh sẽ
 * vượt giới hạn 64 ký tự của MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_stability_assessment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_stability_assessment_list_id'); // -> ..._list.id
            $table->unsignedBigInteger('item_id')->nullable();                   // -> ..._item.id
            $table->string('action', 50);                                        // Thêm mốc / Sửa mốc / Xoá mốc...
            $table->string('target', 100)->nullable();                           // Mốc bị tác động, dạng chữ
            $table->string('old_values', 1000)->nullable();                      // Giá trị trước khi đổi
            $table->string('new_values', 1000)->nullable();                      // Giá trị sau khi đổi
            $table->string('note', 500)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('standard_stability_assessment_list_id', 'ssa_history_list_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_stability_assessment_histories');
    }
};
