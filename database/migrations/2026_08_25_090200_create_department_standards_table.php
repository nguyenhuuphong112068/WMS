<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHẤT CHUẨN CỦA TỪNG PHÒNG BAN
 *
 * Danh mục chất chuẩn (standard_categories) dùng chung toàn công ty vì nó mô tả BẢN
 * CHẤT của chất chuẩn: tên, số CAS, nguồn gốc, version, phân nhóm chuẩn, đơn vị gốc.
 * Hai phòng khai khác nhau ở những cột đó thì có một phòng sai.
 *
 * Nhưng CÁCH DÙNG lại là chuyện riêng của từng phòng: hạn dùng nội bộ sau khi mở ống,
 * để ở định khu nào, ngưỡng tồn tối thiểu. Bảng này giữ đúng phần đó.
 *
 * Ngoài ra, CÓ DÒNG Ở ĐÂY = PHÒNG ĐÓ ĐƯỢC DÙNG CHẤT CHUẨN NÀY - đúng cách cột QC /
 * QC1 / QC2 / AD trên danh mục chất chuẩn giấy đang đánh dấu.
 *
 * Quy tắc đọc giá trị (fallback, xem App\Support\DepartmentStandard):
 *
 *      giá trị hiệu lực = department_standards.<cột> ?? standard_categories.<cột>
 *
 * default_location_id : vị trí lưu trữ QUY HOẠCH - chỉ dùng để điền sẵn khi nhập.
 *                       Vị trí THỰC TẾ của từng ống nằm ở standard_imports.location_id.
 * min_stock           : tồn xuống dưới mức này thì coi là sắp hết, theo đơn vị gốc
 *                       của danh mục chất chuẩn (standard_categories.unit_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_standards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');                    // -> deparments.id
            $table->unsignedBigInteger('category_id');                      // -> standard_categories.id

            $table->unsignedSmallInteger('shelf_life_months')->nullable();  // null = theo danh mục
            $table->unsignedBigInteger('default_location_id')->nullable();  // -> locations.id
            $table->unsignedBigInteger('storage_condition_id')->nullable(); // -> storage_conditions.id
            $table->decimal('min_stock', 15, 4)->nullable();                // Ngưỡng cảnh báo sắp hết

            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Mỗi phòng chỉ có một dòng cấu hình cho một chất chuẩn
            $table->unique(['department_id', 'category_id'], 'department_standards_unique');
            $table->index('category_id', 'department_standards_category_id_index');
            $table->index('default_location_id', 'department_standards_location_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_standards');
    }
};
