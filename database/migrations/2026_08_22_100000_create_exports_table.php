<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - PHIẾU SỬ DỤNG HOÁ CHẤT
 *
 * Mỗi bản ghi là một lần lấy hoá chất ra khỏi kho từ một phiếu nhập cụ thể
 * (sử dụng cho công việc, hoặc huỷ bỏ khi hỏng / hết hạn).
 *
 * code       : KHÔNG sinh mới. Lấy đúng imports.code của phiếu nhập được xuất ra
 *              (imports.code là duy nhất). Một phiếu nhập có thể xuất nhiều lần
 *              nên code ở bảng này bị lặp -> không đặt unique.
 * import_id  : -> imports.id. Lưu kèm code để join theo khoá số, không join chuỗi;
 *              đồng thời là căn cứ tính tồn còn lại của phiếu nhập.
 * amount     : số lượng lấy ra, theo đơn vị gốc của danh mục hoá chất
 *              (chemical_categories.unit_id) giống bảng imports.
 * type       : 'export' = sử dụng, 'cancel' = huỷ bỏ. Cả hai đều trừ tồn kho,
 *              tách ra để thống kê phần hao hụt do huỷ.
 * purpose    : mục đích sử dụng / lý do huỷ.
 * checked_by : người kiểm tra, xác nhận lần xuất này.
 *
 * department_id : phòng ban thực hiện, lấy từ session('user')['selected_department_id'].
 * status_id     : 1 = hiệu lực (có trừ tồn), 0 = đã khoá (không trừ tồn). Không xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);                             // = imports.code của phiếu nhập
            $table->unsignedBigInteger('import_id');                // -> imports.id
            $table->unsignedBigInteger('department_id');            // -> deparments.id
            $table->decimal('amount', 15, 4)->default(0);           // Số lượng lấy ra
            $table->enum('type', ['export', 'cancel'])->default('export'); // Sử dụng / Huỷ bỏ
            $table->date('exported_date');                          // Ngày sử dụng
            $table->string('exported_by', 255)->nullable();         // Người sử dụng
            $table->string('purpose', 500)->nullable();             // Mục đích sử dụng / lý do huỷ
            $table->string('checked_by', 255)->nullable();          // Người kiểm tra
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('code', 'exports_code_index');
            $table->index('import_id', 'exports_import_id_index');
            $table->index('department_id', 'exports_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
