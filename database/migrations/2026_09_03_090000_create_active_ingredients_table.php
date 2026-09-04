<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - TÊN HOẠT CHẤT
 *
 * Danh sách hoạt chất (tên / số CAS / công thức). Cột is_table_a đánh dấu chất nào thuộc
 * BẢNG A Phụ lục IV Nghị định 24/2026/NĐ-CP - "Danh mục hoá chất phải xây dựng Kế hoạch
 * phòng ngừa, ứng phó sự cố hoá chất".
 *
 * is_table_a   : 1 = chất thuộc Bảng A (có ngưỡng threshold_kg, và là điều kiện tiên quyết
 *                để một hỗn hợp chứa nó bị xét theo Bảng B). 0 = chất thường, chỉ lưu để
 *                gán cho tên hoá chất.
 * threshold_kg : "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)" - chỉ
 *                có ý nghĩa khi is_table_a = 1. Tồn trữ toàn công ty vượt ngưỡng này thì cơ
 *                sở phải lập Kế hoạch phòng ngừa, ứng phó sự cố hoá chất.
 * is_statutory : 1 = dòng lấy từ nghị định (seed). Vẫn sửa được khi nghị định thay đổi,
 *                nhưng lịch sử ghi rõ là sửa dữ liệu luật định.
 *
 * Bảng B (hỗn hợp phân loại theo GHS) làm ở bước sau bằng bảng riêng, KHÔNG nằm ở bảng này.
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá) - cột chuẩn của mọi bảng nghiệp vụ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                       // Mã tự sinh A00001
            $table->string('name')->unique();                           // Tên hoạt chất (tiếng Việt như trong nghị định)
            $table->string('name_en')->nullable();                      // Tên tiếng Anh / tên gọi khác
            $table->string('cas_no', 100)->nullable();                  // Số CAS
            $table->string('chemical_formula', 255)->nullable();        // Công thức hoá học
            $table->tinyInteger('is_table_a')->default(0);              // 1 = thuộc Bảng A Phụ lục IV NĐ 24/2026
            $table->decimal('threshold_kg', 15, 3)->nullable();         // Ngưỡng tồn trữ lớn nhất tại một thời điểm (kg) - chỉ khi is_table_a = 1
            $table->string('legal_ref', 255)->default('Nghị định 24/2026/NĐ-CP - Phụ lục IV - Bảng A');
            $table->tinyInteger('is_statutory')->default(1);            // 1 = dữ liệu luật định (seed)
            $table->string('note', 255)->nullable();
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('cas_no', 'active_ingredients_cas_no_index');
            $table->index('name', 'active_ingredients_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_ingredients');
    }
};
