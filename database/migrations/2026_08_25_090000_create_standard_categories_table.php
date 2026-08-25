<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - DANH MỤC CHẤT CHUẨN
 *
 * Chất chuẩn (reference standard) là chất dùng làm mốc so sánh khi kiểm nghiệm.
 *
 * VÌ SAO KHÔNG DÙNG CHUNG BẢNG VỚI chemical_categories:
 * quy tắc "loại hàng là dữ liệu, không phải kiến trúc" chỉ áp dụng khi NGHIỆP VỤ
 * GIỐNG NHAU. Chất chuẩn khác hẳn hoá chất ở ba điểm không gộp được bằng một cột
 * phân loại: mã danh mục theo dãy riêng (S00001), có phân nhóm chuẩn 7 nhóm quyết
 * định mã ống chuẩn lúc nhập, và có version - chất chuẩn được cấp lại version mới
 * khi nhà sản xuất phát hành lô giá trị mới.
 *
 * Ngược lại, các bảng dữ liệu gốc thì DÙNG CHUNG với hoá chất đúng theo quy tắc:
 * chem_names (tên chất), manufacturers (nguồn gốc/NSX), units, storage_conditions,
 * suppliers - vì bản chất "tên một chất" hay "một nhà sản xuất" là như nhau.
 *
 * code    : sinh tự động dạng S00001, tăng dần mỗi lần thêm mới.
 * cas_no  : số CAS của chất chuẩn. Điền sẵn theo chem_names.cas_no khi chọn tên
 *           nhưng vẫn sửa được - cùng một tên chất, chuẩn tạp có số CAS riêng.
 * version : phiên bản chất chuẩn do nhà sản xuất công bố, bắt đầu từ 0. Đổi version
 *           là đổi giá trị công bố nên phải duyệt lại.
 * groups  : danh sách mã nhóm chuẩn, lưu dạng chuỗi JSON, ví dụ ["PRS","VKN"].
 *           Danh sách mã đầy đủ khai báo tại config/standard.php.
 *
 * unit_id : đơn vị tính GỐC dùng để lưu tồn kho, giống cách danh mục hoá chất làm.
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                           // Mã chất chuẩn (S00001)
            $table->unsignedBigInteger('chem_names_id');                    // -> chem_names.id (Tên chất chuẩn)
            $table->string('cas_no', 50)->nullable();                       // Số CAS
            $table->unsignedBigInteger('manufacturers_id');                 // -> manufacturers.id (Nguồn gốc/NSX)
            $table->unsignedBigInteger('unit_id');                          // -> units.id
            $table->unsignedBigInteger('storage_condition_id')->nullable(); // -> storage_conditions.id
            $table->unsignedInteger('version')->default(0);                 // Version chất chuẩn
            $table->string('groups', 255)->nullable();                      // Mảng mã nhóm chuẩn (JSON)
            $table->unsignedSmallInteger('shelf_life_months')->nullable();  // Hạn dùng mặc định (tháng)
            $table->string('doc_no', 20)->nullable();                       // Số tài liệu
            $table->string('note', 500)->nullable();                        // Ghi chú
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('chem_names_id', 'standard_categories_chem_names_id_index');
            $table->index('manufacturers_id', 'standard_categories_manufacturers_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_categories');
    }
};
