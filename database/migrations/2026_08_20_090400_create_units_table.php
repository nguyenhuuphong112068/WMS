<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - ĐƠN VỊ TÍNH
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('short_name', 100)->unique();                // Ký hiệu (kg, L, thùng...)
            $table->string('name')->unique();                           // Tên đơn vị tính
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
        Schema::dropIfExists('units');
    }
};
