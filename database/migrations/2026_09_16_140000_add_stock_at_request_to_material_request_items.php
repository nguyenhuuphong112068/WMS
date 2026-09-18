<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cột "Tồn" ở dòng đề nghị cấp phát vật tư: chụp tồn kho của danh mục tại đúng thời điểm
 * dòng được thêm vào đề nghị (lúc Trình ký hoặc Lưu tạm) - không tính lại sau đó, khác với
 * "Tồn khả dụng" (tính động, không lưu DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('material_request_items', 'stock_at_request')) {
                $table->decimal('stock_at_request', 15, 4)->nullable()->after('requested_unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('material_request_items', 'stock_at_request')) {
                $table->dropColumn('stock_at_request');
            }
        });
    }
};
