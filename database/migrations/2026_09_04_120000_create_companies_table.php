<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - CÔNG TY
 *
 * Phần mềm triển khai cho nhiều công ty, mỗi công ty có nhiều phòng ban riêng.
 * Việc đối chiếu "Ngưỡng khối lượng tồn trữ lớn nhất" (Phụ lục IV NĐ 24/2026/NĐ-CP)
 * chỉ cộng trong phạm vi các phòng ban thuộc cùng một công ty.
 *
 * Cột chuẩn theo quy tắc dự án: id, status_id, created_by, updated_by, timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->unique();
                $table->string('short_name')->unique();
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
