<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - DANH MỤC HOÁ CHẤT
 *
 * code           : sinh tự động theo dạng H00001, tăng dần mỗi lần thêm mới.
 * density        : tỉ trọng d (g/ml), dùng để quy đổi giữa đơn vị khối lượng và thể tích
 *                  (xem App\Support\UnitConverter).
 * unit_id        : đơn vị tính GỐC dùng để lưu tồn kho. Phòng ban xuất bằng đơn vị khác
 *                  thì quy đổi về đơn vị này khi ghi sổ, không lưu mỗi phòng một đơn vị.
 * Không lưu nhà cung cấp ở đây - nhà cung cấp là thông tin của từng lần mua, không phải
 * thuộc tính của hoá chất trong danh mục.
 *
 * classification : danh sách nhóm phân loại theo Nghị định hoá chất (PL1..PL4, N1..N10, CAM).
 *                  Lưu dạng chuỗi JSON các mã, ví dụ ["PL2","N1","N9"].
 *                  Danh sách mã và tên đầy đủ khai báo tại config/chemical.php.
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                       // Mã danh mục
            $table->string('type', 30)->nullable();                     // Loại hoá chất
            $table->unsignedBigInteger('chem_names_id');                // -> chem_names.id
            $table->unsignedBigInteger('manufacturers_id');             // -> manufacturers.id
            $table->unsignedBigInteger('unit_id');                      // -> units.id
            $table->decimal('density', 10, 4)->nullable();              // Tỉ trọng d (g/ml)
            $table->unsignedSmallInteger('shelf_life_months')->nullable(); // Hạn dùng mặc định (tháng)
            $table->string('storage_condition', 255)->nullable();       // Điều kiện bảo quản
            $table->string('doc_no', 20)->nullable();                   // Số tài liệu
            $table->string('classification', 255)->nullable();          // Mảng mã phân loại (JSON)
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('chem_names_id', 'chemical_categories_chem_names_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_categories');
    }
};
