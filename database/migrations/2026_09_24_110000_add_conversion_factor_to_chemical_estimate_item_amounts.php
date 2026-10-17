<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hệ số quy đổi của một dòng số lượng dự trù hoá chất về ĐƠN VỊ DANH MỤC CỦA PHÒNG
 * (chemical_department_categories.unit_id): 1 đơn vị dự trù = conversion_factor đơn vị
 * danh mục (ví dụ dự trù theo "chai", danh mục tính "g" -> 500 g/chai).
 *
 * Bắt buộc khai với hoá chất nhóm 9 / 10 (Phụ lục IV NĐ 24/2026) khi dự trù bằng đơn vị
 * khác đơn vị danh mục, để quy được lượng dự trù ra kg đối chiếu ngưỡng tồn trữ -
 * xem App\Support\ChemicalEstimateThreshold. NULL = dòng khai đúng đơn vị danh mục.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_estimate_item_amounts', function (Blueprint $table) {
            $table->decimal('conversion_factor', 18, 6)->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_estimate_item_amounts', function (Blueprint $table) {
            $table->dropColumn('conversion_factor');
        });
    }
};
