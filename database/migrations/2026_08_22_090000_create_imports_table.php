<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - PHIẾU NHẬP HOÁ CHẤT
 *
 * code : mã xuất nhập, sinh tự động theo quy tắc
 *        department_id + category_id + số thứ tự 8 chữ số (bắt đầu từ 00000001).
 *        Ví dụ phòng ban id = 2, danh mục hoá chất id = 15, lần nhập đầu tiên
 *        của cặp đó -> 21500000001.
 *        Số thứ tự đếm riêng cho từng cặp (phòng ban, danh mục), xem
 *        App\Http\Controllers\Pages\Import\ChemicalImportController::nextCode().
 *
 * department_id : phòng ban thực hiện nhập, lấy từ session('user')['selected_department_id'].
 *                 Bắt buộc lưu vì mã xuất nhập được sinh từ giá trị này.
 * category_id   : -> chemical_categories.id (hoá chất được nhập)
 * amount        : số lượng nhập, tính theo đơn vị gốc của danh mục hoá chất
 *                 (chemical_categories.unit_id), không lưu đơn vị riêng ở đây.
 *
 * status_id : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá). Không xoá cứng phiếu nhập.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                       // Mã xuất nhập (sinh tự động)
            $table->unsignedBigInteger('department_id');                // -> deparments.id
            $table->unsignedBigInteger('category_id');                  // -> chemical_categories.id
            $table->decimal('amount', 15, 4)->default(0);               // Số lượng nhập
            $table->date('imported_date');                              // Ngày nhập kho
            $table->string('imported_by', 255)->nullable();             // Người nhập kho
            $table->string('invoice_number', 100)->nullable();          // Số hoá đơn
            $table->date('invoice_date')->nullable();                   // Ngày hoá đơn
            $table->date('expired_date')->nullable();                   // Hạn sử dụng
            $table->boolean('is_microbiological_chemicals')->default(false); // Hoá chất vi sinh
            $table->string('batch_no', 100)->nullable();                // Số lô
            $table->unsignedBigInteger('supplier_id')->nullable();      // -> suppliers.id
            $table->string('note', 500)->nullable();                    // Ghi chú
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'imports_department_id_index');
            $table->index('category_id', 'imports_category_id_index');
            $table->index('supplier_id', 'imports_supplier_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
