<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part Number của từng vật tư dự trù (mã linh kiện / mã đặt hàng của nhà sản xuất) -
 * người lập phiếu khai ở modal "Thêm Vật Tư Dự Trù" để Cung Ứng đặt đúng hàng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_estimate_items', 'part_number')) {
            Schema::table('material_estimate_items', function (Blueprint $table) {
                $table->string('part_number', 100)->nullable()->after('material_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('material_estimate_items', 'part_number')) {
            Schema::table('material_estimate_items', function (Blueprint $table) {
                $table->dropColumn('part_number');
            });
        }
    }
};
