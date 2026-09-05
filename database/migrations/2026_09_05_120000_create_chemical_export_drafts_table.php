<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - PHIẾU TẠM (giỏ chọn nhiều hoá chất từ tồn kho, chưa trừ kho)
 *
 * Sinh ra từ picker "Chọn Nhiều Từ Tồn Kho" ở modal Sử Dụng Hoá Chất, khi người dùng
 * bấm Lưu Tạm thay vì Sử Dụng Ngay. CHỈ chứa dòng loại 'export' (Sử dụng) - Loại bỏ
 * và Chuyển kho luôn trừ kho ngay, không bao giờ nằm ở bảng này, nên không cần cột
 * type / to_department_id / test_report_no.
 *
 * batch_code : gom các dòng được lưu cùng một lượt bấm Lưu Tạm, để hiện thành 1 đợt
 *              trên tab "Phiếu Tạm".
 * import_id  : -> chemical_imports.id, phiếu nhập được chọn để xuất.
 * amount     : số lượng dự kiến lấy ra, chưa kiểm tra chặt hạn mức / tồn còn lại -
 *              việc đó chỉ làm thật khi bấm "Dùng Ngay" ở tab Phiếu Tạm (tồn có thể
 *              đã đổi từ lúc lưu tạm đến lúc dùng).
 *
 * Không có status_id: đây là giỏ nháp, không phải chứng từ - xoá là xoá cứng. Khi
 * chuyển thành phiếu thật thì chemical_exports mới là nơi giữ vết đầy đủ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_export_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 40);
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('import_id');
            $table->decimal('amount', 15, 4);
            $table->string('purpose', 500)->nullable();
            $table->string('checked_by', 255)->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'batch_code'], 'chemical_export_drafts_dept_batch_index');
            $table->index('import_id', 'chemical_export_drafts_import_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_export_drafts');
    }
};
