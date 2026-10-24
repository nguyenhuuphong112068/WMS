<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kết quả hiệu chuẩn TỪNG ĐƠN VỊ vật tư: QA nhập Seri No + Đạt / Không đạt cho mỗi đơn vị của
 * phiếu đề nghị hiệu chuẩn (material_calibrations). Phiếu có thể đạt một phần: phần đạt ở dòng
 * passed, phần không đạt tách thành dòng failed (sang Cách ly) - calibration_id trỏ đúng dòng
 * kết quả của đơn vị đó.
 *
 * Số lượng lẻ (không tách được đơn vị) thì một bản ghi cho cả dòng, unit_no = 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_calibration_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calibration_id');                 // -> material_calibrations.id (dòng kết quả)
            $table->string('code', 40);                                   // mã phiếu hiệu chuẩn
            $table->unsignedBigInteger('import_id');                      // -> material_imports.id
            $table->unsignedInteger('unit_no')->default(0);               // đơn vị thứ mấy của mã xuất nhập trong phiếu
            $table->decimal('amount', 15, 4)->default(1);                 // 1 đơn vị, hoặc cả dòng khi số lượng lẻ
            $table->string('serial_no', 100)->nullable();
            $table->string('result', 20);                                 // passed | failed
            $table->string('result_by')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('calibration_id', 'material_calibration_units_calibration_index');
            $table->index('code', 'material_calibration_units_code_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_calibration_units');
    }
};
