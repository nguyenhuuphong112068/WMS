<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VẬT TƯ CỦA TỪNG PHÒNG BAN
 *
 * Danh mục vật tư (material_categories) dùng chung toàn công ty vì nó mô tả BẢN CHẤT của
 * vật tư: tên, nhà sản xuất, thông tin kỹ thuật. Hai phòng khai khác nhau ở những cột đó
 * thì có một phòng sai.
 *
 * Nhưng CÁCH DÙNG lại là chuyện riêng của từng phòng: phân loại nhóm nào, đơn vị tính,
 * ngưỡng tồn tối thiểu. Bảng này giữ đúng phần đó.
 *
 * Ngoài ra, CÓ DÒNG Ở ĐÂY = PHÒNG ĐÓ ĐƯỢC DÙNG VẬT TƯ NÀY. Nhờ vậy các phòng ban không
 * phải cùng nhìn thấy toàn bộ danh mục vật tư của công ty. Cột "Phòng Ban Đang Dùng" ở
 * tab Danh Mục Vật Tư Công Ty đọc từ đây.
 *
 * classification_id : -> material_classifications.id, phân loại theo bộ nhóm của phòng.
 * unit_id           : -> units.id, đơn vị phòng dùng để nhập / xuất / ghi tồn vật tư này.
 *                     Song song với hoá chất / chất chuẩn, đơn vị là cách dùng của phòng
 *                     chứ không phải bản chất vật tư nên nằm ở đây.
 * min_stock         : tồn xuống dưới mức này thì coi là sắp hết, theo đơn vị ở trên.
 *
 * Không xoá cứng: khoá (status_id = 0) để giữ lại vết đã từng khai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');                  // -> deparments.id
            $table->unsignedBigInteger('category_id');                    // -> material_categories.id
            $table->unsignedBigInteger('classification_id')->nullable();  // -> material_classifications.id
            $table->unsignedBigInteger('unit_id')->nullable();            // -> units.id
            $table->decimal('min_stock', 15, 4)->nullable();              // Ngưỡng cảnh báo sắp hết

            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Mỗi phòng chỉ có một dòng khai cho một vật tư
            $table->unique(['department_id', 'category_id'], 'department_materials_unique');
            $table->index('category_id', 'department_materials_category_id_index');
            $table->index('classification_id', 'department_materials_classification_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_materials');
    }
};
