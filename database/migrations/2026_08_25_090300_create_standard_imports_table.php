<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - PHIẾU NHẬP CHẤT CHUẨN
 *
 * MÃ ỐNG CHUẨN (cột code) - quy tắc riêng của chất chuẩn, khác hẳn mã xuất nhập
 * của hoá chất:
 *
 *      deparments.shortName + mã nhóm chuẩn + yy + mm + số thứ tự (4 chữ số)
 *      QC1              +   VKN         + 26 + 01 + 0036   ->  QC1VKN26010036
 *
 * - shortName : phòng ban thực hiện nhập, lấy từ session('user')['selected_department'].
 * - mã nhóm   : nhóm chuẩn của ĐÚNG ống này, lấy từ config('standard.groups').*.code.
 *               Một chất chuẩn thuộc nhiều nhóm nên nhóm được chọn lúc nhập, không
 *               suy ra từ danh mục.
 * - yy / mm   : năm 2 số và tháng 2 số của NGÀY NHẬP.
 * - số thứ tự : đếm trong NĂM, riêng cho từng cặp (phòng ban, nhóm chuẩn).
 *
 * seq_year / seq_no lưu thẳng phần đếm thay vì cắt chuỗi từ code khi cần cấp mã mới:
 * mã nhóm dài ngắn khác nhau (CN 2 ký tự, IMP 3 ký tự) nên cắt chuỗi rất dễ sai.
 * Xem App\Support\StandardCode.
 *
 * amount : số lượng nhập, theo đơn vị gốc của danh mục chất chuẩn
 *          (standard_categories.unit_id), không lưu đơn vị riêng ở đây.
 *
 * status_id : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá). Không xoá cứng phiếu nhập.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_imports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                       // Mã ống chuẩn (sinh tự động)
            $table->unsignedBigInteger('department_id');                // -> deparments.id
            $table->unsignedBigInteger('category_id');                  // -> standard_categories.id
            $table->string('group_code', 10);                           // Mã nhóm chuẩn dùng trong mã ống
            $table->smallInteger('seq_year');                           // Năm của số thứ tự
            $table->unsignedInteger('seq_no');                          // Số thứ tự trong năm

            $table->decimal('amount', 15, 4)->default(0);               // Số lượng nhập
            $table->date('imported_date');                              // Ngày nhập kho
            $table->string('imported_by', 255)->nullable();             // Người nhập kho
            $table->string('invoice_number', 100)->nullable();          // Số hoá đơn
            $table->date('invoice_date')->nullable();                   // Ngày hoá đơn
            $table->date('expired_date')->nullable();                   // Hạn sử dụng của nhà sản xuất
            $table->date('internal_expired_date')->nullable();          // Hạn dùng nội bộ sau khi mở ống
            $table->string('batch_no', 100)->nullable();                // Số lô
            $table->string('coa_no', 100)->nullable();                  // Số phiếu kiểm nghiệm gốc (CoA)
            $table->unsignedBigInteger('supplier_id')->nullable();      // -> suppliers.id
            $table->unsignedBigInteger('location_id')->nullable();      // -> locations.id
            $table->string('note', 500)->nullable();                    // Ghi chú
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'standard_imports_department_id_index');
            $table->index('category_id', 'standard_imports_category_id_index');
            $table->index('supplier_id', 'standard_imports_supplier_id_index');
            // Cấp mã mới: tìm số thứ tự lớn nhất của (phòng ban, nhóm chuẩn, năm)
            $table->index(['department_id', 'group_code', 'seq_year'], 'standard_imports_seq_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_imports');
    }
};
