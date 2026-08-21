<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH MỤC - DANH MỤC VẬT TƯ
 *
 * Một dòng danh mục = một tổ hợp Tên vật tư + Nhà sản xuất + Nhà cung cấp + Đơn vị tính.
 *
 * app_status : trạng thái phê duyệt (pending | approved | rejected)
 * status_id  : trạng thái sử dụng (1 = hoạt động, 0 = đã khoá)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_names_id');            // -> material_names.id
            $table->unsignedBigInteger('manufacturers_id');             // -> manufacturers.id
            $table->unsignedBigInteger('suppliers_id');                 // -> suppliers.id
            $table->unsignedBigInteger('unit_id');                      // -> units.id
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Không cho khai báo trùng cùng một tổ hợp
            $table->unique(
                ['material_names_id', 'manufacturers_id', 'suppliers_id', 'unit_id'],
                'material_categories_combo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_categories');
    }
};
