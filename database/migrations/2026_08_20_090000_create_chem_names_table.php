<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - TÊN HOÁ CHẤT
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá) - cột chuẩn của mọi bảng nghiệp vụ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chem_names', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                           // Tên hoá chất
            $table->string('active_ingredient_name')->nullable();       // Tên hoạt chất
            $table->string('cas_no', 100)->nullable();                  // Số CAS
            $table->string('doc_no', 100)->nullable();                  // Số tài liệu
            $table->string('chemical_formula', 255)->nullable();        // Công thức hoá học
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chem_names');
    }
};
