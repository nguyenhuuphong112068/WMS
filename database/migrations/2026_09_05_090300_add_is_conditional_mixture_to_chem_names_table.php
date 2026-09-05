<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHÓM 2 (hình 1) - "Hỗn hợp chất sản xuất, kinh doanh có điều kiện (Phụ lục II nhóm 2)".
 *
 * Nghị định 24/2026/NĐ-CP không cho công thức tự suy nhóm 2 (khác nhóm 8, nhóm 10) nên
 * người dùng TỰ TICK ô này trên màn "Tên Hoá Chất".
 *
 * 1 = hỗn hợp thuộc Phụ lục II nhóm 2. 0 = không.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chem_names') && ! Schema::hasColumn('chem_names', 'is_conditional_mixture')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->tinyInteger('is_conditional_mixture')->default(0)->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chem_names') && Schema::hasColumn('chem_names', 'is_conditional_mixture')) {
            Schema::table('chem_names', function (Blueprint $table) {
                $table->dropColumn('is_conditional_mixture');
            });
        }
    }
};
