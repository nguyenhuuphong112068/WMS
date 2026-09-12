<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHUYỂN PHÂN LOẠI VẬT TƯ VỀ DANH MỤC VẬT TƯ CÔNG TY
 *
 * Ngược lại với migration 2026_08_28_100200_split_material_categories_by_department: phân
 * loại từng phòng tự khai (material_classifications + material_department_categories.classification_id)
 * bị bỏ hẳn, thay bằng cột material_categories.classification.
 *
 * Lý do: "vật tư đắt hay rẻ, quan trọng tới đâu, có nguy hiểm không, có phải hiệu chuẩn
 * trước khi dùng không" là BẢN CHẤT của vật tư, hai phòng khai lệch nhau thì một phòng
 * sai - giống tên vật tư và nhà sản xuất, phải dùng chung toàn công ty.
 *
 * classification lưu JSON nhiều tiêu chí cùng lúc (giá / mức độ quan trọng / nguồn cấp
 * phát / hàng nguy hiểm / cần hiệu chuẩn), xem App\Support\MaterialClassification.
 *
 * Thêm cùng đợt, cũng ở mức công ty:
 *   - purchasing_department : bộ phận chịu trách nhiệm mua (Hành Chánh / Cung Ứng / IT)
 *   - lead_time_days        : số ngày từ lúc đặt hàng tới lúc hàng về công ty
 *
 * Dữ liệu phân loại cũ của các phòng KHÔNG chuyển sang được: bộ nhóm cũ là tên tự do do
 * từng phòng đặt, không khớp bộ tiêu chí cố định mới. Các vật tư sẽ ở trạng thái "Chưa
 * phân loại" cho tới khi được khai lại ở danh mục công ty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->json('classification')->nullable();
            $table->string('purchasing_department', 20)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->json('classification')->nullable();
            $table->string('purchasing_department', 20)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
        });

        // Index của cột này đã qua một lần đổi tên bảng (2026_09_01_090000) nên tên index
        // trên từng CSDL có thể khác nhau - tìm theo cột thay vì gọi đúng tên.
        foreach (Schema::getIndexes('material_department_categories') as $index) {
            if ($index['columns'] === ['classification_id']) {
                Schema::table('material_department_categories', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index['name']);
                });
            }
        }

        Schema::table('material_department_categories', function (Blueprint $table) {
            $table->dropColumn('classification_id');
        });

        Schema::dropIfExists('material_classifications');
    }

    public function down(): void
    {
        Schema::create('material_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('department_id');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'name'], 'material_classifications_dept_name_unique');
            $table->index('department_id', 'material_classifications_department_id_index');
        });

        Schema::table('material_department_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->index('classification_id', 'material_department_categories_classification_id_index');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn(['classification', 'purchasing_department', 'lead_time_days']);
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn(['classification', 'purchasing_department', 'lead_time_days']);
        });
    }
};
