<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - LỊCH SỬ ĐIỀU CHỈNH PHIẾU NHẬP HOÁ CHẤT
 *
 * Mỗi lần Thêm mới / Điều chỉnh / Khoá / Mở khoá một dòng ở imports sẽ ghi thêm một
 * dòng ở đây, chụp lại giá trị của phiếu NGAY SAU khi thay đổi.
 *
 * - change_note : mô tả cụ thể nội dung đã đổi theo dạng "Trường: cũ -> mới"
 * - reason      : lý do điều chỉnh do người dùng nhập, bắt buộc khi điều chỉnh
 *
 * Bản ghi lịch sử chỉ ghi thêm, không sửa và không xoá - ghi sai thì điều chỉnh
 * lại lần nữa, đúng cách inventory_balancings đang làm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_import_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');                    // -> imports.id
            $table->string('action', 30);                               // Thêm mới | Điều chỉnh | Khoá | Mở khoá

            // Ảnh chụp giá trị phiếu nhập sau khi thay đổi
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->date('imported_date')->nullable();
            $table->string('imported_by', 255)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('expired_date')->nullable();
            $table->date('internal_expired_date')->nullable();
            $table->boolean('is_microbiological_chemicals')->nullable();
            $table->string('batch_no', 100)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                    // Nội dung đã đổi
            $table->string('reason', 500)->nullable();                  // Lý do điều chỉnh
            $table->string('created_by')->nullable();                   // Người thực hiện
            $table->timestamp('created_at')->nullable();                // Thời điểm thực hiện

            $table->index('import_id', 'chemical_import_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_import_histories');
    }
};
