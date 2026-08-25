<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - PHIẾU SỬ DỤNG CHẤT CHUẨN
 *
 * Mỗi bản ghi là một lần lấy chất chuẩn ra khỏi kho từ một ống chuẩn cụ thể
 * (sử dụng để kiểm nghiệm, hoặc huỷ bỏ khi hỏng / hết hạn).
 *
 * code       : KHÔNG sinh mới. Lấy đúng standard_imports.code của ống được xuất ra
 *              (mã ống chuẩn là duy nhất). Một ống xuất được nhiều lần nên code ở
 *              bảng này bị lặp -> không đặt unique.
 * import_id  : -> standard_imports.id. Lưu kèm code để join theo khoá số, đồng thời
 *              là căn cứ tính tồn còn lại của ống.
 * amount     : số lượng lấy ra, theo đơn vị gốc của danh mục chất chuẩn.
 * type       : 'export' = sử dụng, 'cancel' = huỷ bỏ. Cả hai đều trừ tồn, tách ra
 *              để thống kê phần hao hụt do huỷ.
 * test_report_no : số phiếu kiểm nghiệm dùng chất chuẩn này, hoặc căn cứ loại bỏ
 *              (OOS, BCSL) khi là phiếu huỷ. Chất chuẩn luôn phải truy được đã dùng
 *              cho phép thử nào nên cột này quan trọng hơn ở hoá chất.
 *
 * status_id  : 1 = hiệu lực (có trừ tồn), 0 = đã khoá (không trừ tồn). Không xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_exports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);                             // = standard_imports.code của ống chuẩn
            $table->unsignedBigInteger('import_id');                // -> standard_imports.id
            $table->unsignedBigInteger('department_id');            // -> deparments.id
            $table->decimal('amount', 15, 4)->default(0);           // Số lượng lấy ra
            $table->enum('type', ['export', 'cancel'])->default('export'); // Sử dụng / Huỷ bỏ
            $table->date('exported_date');                          // Ngày sử dụng
            $table->string('exported_by', 255)->nullable();         // Người sử dụng
            $table->string('purpose', 500)->nullable();             // Mục đích sử dụng / lý do huỷ
            $table->string('test_report_no', 100)->nullable();      // Số phiếu KN / OOS / BCSL
            $table->string('checked_by', 255)->nullable();          // Người kiểm tra
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('code', 'standard_exports_code_index');
            $table->index('import_id', 'standard_exports_import_id_index');
            $table->index('department_id', 'standard_exports_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_exports');
    }
};
