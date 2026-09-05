<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * % KHỐI LƯỢNG CỦA TỪNG THÀNH PHẦN TRONG HỖN HỢP (chem_name_active_ingredient).
 *
 * Cần cho quy tắc nhóm 8 (hình 1 - Nghị định 24/2026/NĐ-CP): một hỗn hợp thuộc
 * "Hỗn hợp chất cần kiểm soát đặc biệt (Phụ lục III)" khi có thành phần thuộc
 * nhóm 3/4/6/7 với tỉ lệ > 1%, hoặc thành phần thuộc nhóm 5 với tỉ lệ > 5%.
 *
 * null / để trống => coi như 0% khi xét nhóm 8 (không tự đánh nhóm 8 khi chưa khai %).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chem_name_active_ingredient')
            && ! Schema::hasColumn('chem_name_active_ingredient', 'content_percent')) {
            Schema::table('chem_name_active_ingredient', function (Blueprint $table) {
                $table->decimal('content_percent', 7, 4)->nullable()->after('active_ingredients_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chem_name_active_ingredient')
            && Schema::hasColumn('chem_name_active_ingredient', 'content_percent')) {
            Schema::table('chem_name_active_ingredient', function (Blueprint $table) {
                $table->dropColumn('content_percent');
            });
        }
    }
};
