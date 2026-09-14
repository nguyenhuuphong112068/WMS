<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thiết bị liên quan của từng dòng trong danh sách đề nghị theo chu kỳ - cùng ý nghĩa với
 * material_request_items.product_name, được chép sang đề nghị nội bộ lúc tạo đề nghị.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_request_item', function (Blueprint $table) {
            if (! Schema::hasColumn('periodic_request_item', 'product_name')) {
                $table->string('product_name', 255)->nullable()->after('category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodic_request_item', function (Blueprint $table) {
            if (Schema::hasColumn('periodic_request_item', 'product_name')) {
                $table->dropColumn('product_name');
            }
        });
    }
};
