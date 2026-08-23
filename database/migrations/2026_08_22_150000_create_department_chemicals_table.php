<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HOÁ CHẤT CỦA TỪNG PHÒNG BAN
 *
 * Danh mục hoá chất (chemical_categories) dùng chung toàn công ty vì nó mô tả BẢN CHẤT
 * của chất: tên, nhà sản xuất, tỉ trọng, phân loại theo Nghị định, đơn vị gốc. Hai phòng
 * khai khác nhau ở những cột đó thì có một phòng sai.
 *
 * Nhưng CÁCH DÙNG chất lại là chuyện riêng của từng phòng: hạn dùng nội bộ, để ở định khu
 * nào, ngưỡng tồn tối thiểu. Bảng này giữ đúng phần đó.
 *
 * Ngoài ra, CÓ DÒNG Ở ĐÂY = PHÒNG ĐÓ ĐƯỢC DÙNG CHẤT NÀY. Nhờ vậy 16 phòng ban không phải
 * cùng nhìn thấy toàn bộ danh mục hoá chất của công ty khi chọn lúc nhập.
 *
 * Quy tắc đọc giá trị (fallback, xem App\Support\DepartmentChemical):
 *
 *      giá trị hiệu lực = department_chemicals.<cột> ?? chemical_categories.<cột>
 *
 * Cột trên chemical_categories được GIỮ NGUYÊN làm mặc định chung của công ty, không xoá:
 * phòng chưa khai riêng vẫn chạy được, và chemical_category_histories (đang lưu đúng từng
 * cột của chemical_categories) không phải sửa theo.
 *
 * shelf_life_months    : hạn dùng nội bộ mặc định của phòng, tính bằng tháng.
 * default_location_id  : vị trí lưu trữ QUY HOẠCH - chỉ dùng để điền sẵn khi nhập.
 *                        Vị trí THỰC TẾ của từng lô nằm ở imports.location_id, hai cái
 *                        này khác nhau và không thay thế cho nhau được.
 * storage_condition_id : điều kiện bảo quản thực tế của phòng, để trống thì theo danh mục.
 * min_stock            : tồn xuống dưới mức này thì coi là sắp hết, theo đơn vị gốc của
 *                        danh mục hoá chất (chemical_categories.unit_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_chemicals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');                    // -> deparments.id
            $table->unsignedBigInteger('category_id');                      // -> chemical_categories.id

            $table->unsignedSmallInteger('shelf_life_months')->nullable();  // null = theo danh mục
            $table->unsignedBigInteger('default_location_id')->nullable();  // -> locations.id
            $table->unsignedBigInteger('storage_condition_id')->nullable(); // -> storage_conditions.id
            $table->decimal('min_stock', 15, 4)->nullable();                // Ngưỡng cảnh báo sắp hết

            $table->string('note', 500)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Mỗi phòng chỉ có một dòng cấu hình cho một hoá chất
            $table->unique(['department_id', 'category_id'], 'department_chemicals_unique');
            $table->index('category_id', 'department_chemicals_category_id_index');
            $table->index('default_location_id', 'department_chemicals_default_location_id_index');
        });

        $this->seedFromImports();
    }

    /**
     * Sinh sẵn cấu hình cho những cặp (phòng ban, hoá chất) mà phòng ĐÃ TỪNG NHẬP.
     *
     * Chép luôn shelf_life_months đang có trong danh mục xuống, để sau khi chạy migration
     * hệ thống chạy y hệt trước đó - chưa phòng nào bị đổi hạn dùng nội bộ.
     */
    private function seedFromImports(): void
    {
        $pairs = DB::table('imports')
            ->join('chemical_categories', 'imports.category_id', '=', 'chemical_categories.id')
            ->select(
                'imports.department_id',
                'imports.category_id',
                'chemical_categories.shelf_life_months'
            )
            ->distinct()
            ->get();

        if ($pairs->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('department_chemicals')->insert(
            $pairs->map(fn ($pair) => [
                'department_id' => $pair->department_id,
                'category_id' => $pair->category_id,
                'shelf_life_months' => $pair->shelf_life_months,
                'note' => 'Sinh tự động từ các phiếu nhập đã có khi tách cấu hình theo phòng ban.',
                'status_id' => 1,
                'created_by' => 'Hệ thống',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('department_chemicals');
    }
};
