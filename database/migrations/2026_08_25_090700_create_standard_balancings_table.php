<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TỒN - CÂN ĐỐI SỐ LƯỢNG NHẬP CHẤT CHUẨN
 *
 * Phiếu sử dụng được phép xuất vượt tồn tối đa 5% (bù sai số cân đong), nên tồn của
 * một mã ống chuẩn có thể bị âm. Bảng này ghi phần điều chỉnh cộng thêm vào số lượng
 * nhập để đưa tồn về đúng thực tế:
 *
 *      Tồn = standard_imports.amount + SUM(balancing_amount) - đã dùng - đã huỷ
 *
 * code             : = standard_imports.code của ống được cân đối. Một ống cân đối
 *                    được nhiều lần nên code lặp -> không unique.
 * import_id        : -> standard_imports.id. Join theo khoá số thay vì chuỗi.
 * balancing_amount : SỐ ĐIỀU CHỈNH, không phải số lượng mới. Dương = nhập thiếu nên
 *                    cộng thêm, âm = nhập dư nên trừ bớt. Các lần cân đối cộng dồn;
 *                    ghi sai thì cân đối ngược lại chứ không sửa bản ghi cũ.
 *
 * status_id : 1 = có hiệu lực (được cộng vào tồn), 0 = đã khoá. Không xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_balancings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);                          // = standard_imports.code
            $table->unsignedBigInteger('import_id');             // -> standard_imports.id
            $table->unsignedBigInteger('department_id');         // -> deparments.id
            $table->decimal('balancing_amount', 15, 4);          // Số điều chỉnh (+/-)
            $table->string('balancing_by', 255)->nullable();     // Người cân đối
            $table->dateTime('balancing_at');                    // Thời điểm cân đối
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('code', 'standard_balancings_code_index');
            $table->index('import_id', 'standard_balancings_import_id_index');
            $table->index('department_id', 'standard_balancings_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_balancings');
    }
};
