<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nhóm 12 "Hàng hoá đặc biệt" - tick tay ở Dữ Liệu Gốc -> Hoá Chất (chem_names), áp cho cả
 * đơn chất lẫn hỗn hợp. App\Support\ChemicalClassification::groupsByChemName() đọc cột này
 * để gắn nhóm 12, từ đó lan sang danh mục / nhập / xuất / tồn như các nhóm khác.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chem_names') && ! Schema::hasColumn('chem_names', 'is_special_goods')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->tinyInteger('is_special_goods')->default(0)->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chem_names') && Schema::hasColumn('chem_names', 'is_special_goods')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->dropColumn('is_special_goods');
            });
        }
    }
};
