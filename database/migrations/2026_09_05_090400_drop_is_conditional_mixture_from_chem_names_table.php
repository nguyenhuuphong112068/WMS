<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHÓM 2 (hình 1) không còn tick tay - đã đổi sang suy tự động (hỗn hợp >= 2 hoạt chất,
 * có >= 1 thành phần thuộc nhóm 1) giống nhóm 8, xem App\Support\ChemicalClassification.
 * Cột chem_names.is_conditional_mixture không còn dùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chem_names') && Schema::hasColumn('chem_names', 'is_conditional_mixture')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->dropColumn('is_conditional_mixture');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chem_names') && ! Schema::hasColumn('chem_names', 'is_conditional_mixture')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->tinyInteger('is_conditional_mixture')->default(0)->after('name');
            });
        }
    }
};
