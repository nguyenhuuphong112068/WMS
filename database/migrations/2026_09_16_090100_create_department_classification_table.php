<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - PHÂN LOẠI (THEO PHÒNG BAN)
 *
 * Danh mục vật tư công ty chỉ khai được BẢN CHẤT của vật tư (giá trị, mức độ quan trọng,
 * hàng nguy hiểm...). Ngoài ra mỗi phòng còn tự chia nhóm vật tư theo cách riêng của phòng
 * mình, nên bộ nhóm đó là dữ liệu gốc của từng phòng: mỗi dòng thuộc đúng một phòng ban.
 *
 * Không có bước duyệt (app_status) - dữ liệu cấp phòng, rủi ro thấp, giống bảng locations.
 * Khoá (status_id = 0) thay cho xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_classification', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('department_id');        // -> deparments.id
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Trong một phòng không được trùng tên phân loại
            $table->unique(['department_id', 'name'], 'department_classification_dept_name_unique');
            $table->index('department_id', 'department_classification_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_classification');
    }
};
