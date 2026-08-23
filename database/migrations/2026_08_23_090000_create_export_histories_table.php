<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - LỊCH SỬ ĐIỀU CHỈNH PHIẾU SỬ DỤNG HOÁ CHẤT
 *
 * Mỗi lần Thêm mới / Cập nhật / Khoá / Mở khoá một dòng ở exports sẽ ghi thêm một
 * dòng ở đây, chụp lại giá trị của phiếu NGAY SAU khi thay đổi. Nhờ vậy xem lại
 * được phiếu từng mang giá trị nào, ai sửa và sửa lúc nào - phiếu sử dụng trừ
 * thẳng vào tồn kho nên phải truy được nguồn gốc từng lần chỉnh.
 *
 * change_note : mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới", nối bằng " | ".
 *               Người dùng nhập thêm lý do điều chỉnh thì lý do đứng đầu chuỗi.
 *
 * Bảng chỉ ghi thêm, không sửa, không xoá - giống chemical_category_histories
 * và estimate_list_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('export_id');                 // -> exports.id
            $table->string('action', 30);                            // Thêm mới | Cập nhật | Khoá | Mở khoá

            // Ảnh chụp giá trị của phiếu sau khi thay đổi
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->string('type', 20)->nullable();                  // export | cancel
            $table->date('exported_date')->nullable();
            $table->string('exported_by', 255)->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('checked_by', 255)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                 // Nội dung đã đổi + lý do điều chỉnh
            $table->string('created_by')->nullable();                // Người thực hiện
            $table->timestamp('created_at')->nullable();             // Thời điểm thực hiện

            $table->index('export_id', 'export_histories_export_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_histories');
    }
};
