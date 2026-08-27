<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUY ĐỔI ĐƠN VỊ THEO TỪNG MÃ DANH MỤC
 *
 * Từ khi đơn vị tính chuyển xuống danh mục của phòng (department_chemicals /
 * department_standards), CÙNG MỘT MÃ có thể được các phòng khai bằng các đơn vị khác
 * nhau: phòng QC đong theo ml, phòng AD cân theo g, kho tổng đếm theo chai.
 *
 * Lúc hai phòng đụng nhau - rõ nhất là khi CHUYỂN KHO - hệ thống phải biết đổi số lượng
 * từ đơn vị phòng gửi sang đơn vị phòng nhận. App\Support\UnitConverter chỉ đổi được
 * trong cùng nhóm (kg <-> g) hoặc khối lượng <-> thể tích khi có tỉ trọng; dính tới đơn
 * vị đếm (chai, thùng) thì phải biết quy cách của CHÍNH mã đó. Bảng này giữ đúng phần
 * quy cách riêng ấy.
 *
 * factor đọc theo chiều: 1 <from_unit> = factor <to_unit>.
 * Ví dụ 1 chai = 500 ml -> from_unit = chai, to_unit = ml, factor = 500.
 * Chiều ngược lại không cần khai thêm, hệ thống tự lấy 1/factor.
 *
 * category_type là CỘT PHÂN LOẠI (chemical | standard), không tách thành hai bảng song
 * song vì nghiệp vụ quy đổi của hai bên giống hệt nhau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('category_type', 20);                // chemical | standard
            $table->unsignedBigInteger('category_id');          // -> chemical_categories.id | standard_categories.id
            $table->unsignedBigInteger('from_unit_id');         // -> units.id
            $table->unsignedBigInteger('to_unit_id');           // -> units.id
            $table->decimal('factor', 20, 8);                   // 1 from_unit = factor to_unit

            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Một cặp đơn vị của một mã chỉ có đúng một hệ số quy đổi
            $table->unique(
                ['category_type', 'category_id', 'from_unit_id', 'to_unit_id'],
                'category_unit_conversions_unique'
            );
            $table->index(['category_type', 'category_id'], 'category_unit_conversions_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_unit_conversions');
    }
};
