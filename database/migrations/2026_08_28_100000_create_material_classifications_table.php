<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - PHÂN LOẠI VẬT TƯ
 *
 * Trước đây danh mục vật tư phân loại cứng theo nhóm A / B / C cho cả công ty. Mỗi phòng
 * ban lại có cách chia nhóm vật tư riêng, nên phân loại giờ là dữ liệu gốc do từng phòng
 * tự khai: mỗi dòng thuộc đúng một phòng (department_id).
 *
 * Không có bước duyệt (app_status) - đây là dữ liệu cấp phòng, rủi ro thấp, giống bảng
 * groups / locations. Khoá (status_id = 0) thay cho xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('department_id');        // -> deparments.id
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Trong một phòng không được trùng tên phân loại
            $table->unique(['department_id', 'name'], 'material_classifications_dept_name_unique');
            $table->index('department_id', 'material_classifications_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_classifications');
    }
};
