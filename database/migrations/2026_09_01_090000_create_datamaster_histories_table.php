<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - LỊCH SỬ THAY ĐỔI (dùng chung cho mọi màn hình nhóm "Dữ Liệu Gốc")
 *
 * Mỗi lần Thêm mới / Cập nhật / Khoá / Mở khoá / Phê duyệt / Từ chối / Xoá một bản ghi
 * dữ liệu gốc sẽ ghi thêm một dòng ở đây.
 *
 * Các bảng dữ liệu gốc có cột khác nhau (tên hoá chất, đơn vị tính, định khu...) nên
 * không tách mỗi bảng một bảng lịch sử riêng: table_name + record_id chỉ ra bản ghi,
 * snapshot là ảnh chụp giá trị của bản ghi NGAY SAU khi thay đổi dạng {"Nhãn": "Giá trị"}.
 * change_note mô tả nội dung đã đổi theo dạng "Trường: cũ -> mới".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datamaster_histories', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 64);               // bảng dữ liệu gốc: chem_names, units, warehouses...
            $table->unsignedBigInteger('record_id');        // id bản ghi trong bảng đó
            $table->string('action', 30);                   // Thêm mới | Cập nhật | Khoá | Mở khoá | Phê duyệt | Từ chối duyệt | Xoá

            $table->json('snapshot')->nullable();           // Ảnh chụp giá trị sau khi thay đổi
            $table->text('change_note')->nullable();        // Mô tả nội dung đã đổi
            $table->string('created_by')->nullable();       // Người thực hiện
            $table->timestamp('created_at')->nullable();    // Thời điểm thực hiện

            $table->index(['table_name', 'record_id'], 'datamaster_histories_record_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datamaster_histories');
    }
};
