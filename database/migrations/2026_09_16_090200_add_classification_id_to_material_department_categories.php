<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN LOẠI RIÊNG CỦA PHÒNG CHO VẬT TƯ PHÒNG ĐANG DÙNG
 *
 * Trỏ tới dữ liệu gốc department_classification của ĐÚNG phòng đã khai dòng này. Khác với
 * material_categories.classification (bản chất vật tư, dùng chung toàn công ty), cột này
 * là cách phòng tự chia nhóm nên nằm ở bảng cấu hình theo phòng.
 *
 * Để nullable: phòng chưa khai phân loại nào thì vẫn khai được vật tư.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_department_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('classification_id')->nullable()->after('category_id');
            $table->index('classification_id', 'material_dept_categories_classification_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('material_department_categories', function (Blueprint $table) {
            $table->dropIndex('material_dept_categories_classification_id_index');
            $table->dropColumn('classification_id');
        });
    }
};
