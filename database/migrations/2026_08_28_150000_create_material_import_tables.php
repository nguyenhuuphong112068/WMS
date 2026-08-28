<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - PHIẾU NHẬP VẬT TƯ
 *
 * Song song với standard_imports bên chất chuẩn nhưng gọn hơn: vật tư là hàng tiêu hao
 * nên không có nhóm chuẩn, hàm lượng / độ ẩm, kiểm soát khối lượng, chu kỳ retest, hạn
 * dùng nội bộ. Cũng không lưu số lô / nhà cung cấp / số hoá đơn ở đây.
 *
 * MÃ LÔ VẬT TƯ (cột code) sinh tự động:
 *
 *      "VT" + "-" + deparments.shortName + "-" + đuôi ngẫu nhiên 10 ký tự
 *      VT-QC1-7KPMR9J4WD
 *
 * Mã KHÔNG chứa số thứ tự và không gắn với danh mục: khoá / xoá một phiếu nhập
 * không để lại khoảng trống nhìn thấy được qua giao diện. Xem App\Support\MaterialCode.
 *
 * amount    : số lượng nhập, theo đơn vị của phòng (department_materials.unit_id).
 * status_id : 1 = hoạt động, 0 = đã khoá. Không xoá cứng phiếu nhập.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_imports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                       // Mã lô vật tư (sinh tự động)
            $table->unsignedBigInteger('department_id');                // -> deparments.id
            $table->unsignedBigInteger('category_id');                  // -> material_categories.id

            $table->decimal('amount', 15, 4)->default(0);               // Số lượng nhập
            $table->date('imported_date');                              // Ngày nhập kho
            $table->string('imported_by', 255)->nullable();            // Người nhập kho
            $table->date('expired_date')->nullable();                   // Hạn sử dụng (có thể để trống)
            $table->unsignedBigInteger('location_id')->nullable();      // -> locations.id
            $table->string('note', 500)->nullable();                    // Ghi chú
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'material_imports_department_id_index');
            $table->index('category_id', 'material_imports_category_id_index');
        });

        Schema::create('material_import_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_import_id');           // -> material_imports.id
            $table->string('action', 30);                               // Thêm mới | Điều chỉnh | Khoá | Mở khoá

            // Ảnh chụp giá trị phiếu nhập sau khi thay đổi
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->date('imported_date')->nullable();
            $table->string('imported_by', 255)->nullable();
            $table->date('expired_date')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();                    // Nội dung đã đổi
            $table->string('reason', 500)->nullable();                  // Lý do điều chỉnh
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('material_import_id', 'material_import_histories_parent_index');
        });

        Schema::create('material_import_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_import_id');           // -> material_imports.id
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedInteger('file_size')->nullable();
            $table->string('file_type', 100)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('material_import_id', 'material_import_attachments_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_import_attachments');
        Schema::dropIfExists('material_import_histories');
        Schema::dropIfExists('material_imports');
    }
};
