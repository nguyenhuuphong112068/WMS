<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 18/11/2026 HÌNH ẢNH VẬT TƯ (không bắt buộc) ở Danh Mục Vật Tư Công Ty: một ảnh cho mỗi mã vật tư,
 * file nằm trong storage, xem qua route category.materialCategory.image. Bảng lịch sử cũng chụp lại
 * đường dẫn ảnh để biết lần sửa nào đã thay / gỡ ảnh.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['material_categories' => 'technical_specification', 'material_category_histories' => 'technical_specification'] as $table => $after) {
            if (Schema::hasColumn($table, 'image_path')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($after) {
                $blueprint->string('image_path')->nullable()->after($after)
                    ->comment('Hình ảnh vật tư (đường dẫn trong storage), không bắt buộc');
            });
        }
    }

    public function down(): void
    {
        foreach (['material_categories', 'material_category_histories'] as $table) {
            if (Schema::hasColumn($table, 'image_path')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('image_path'));
            }
        }
    }
};
