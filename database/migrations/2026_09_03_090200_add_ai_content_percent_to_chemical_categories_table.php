<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HÀM LƯỢNG HOẠT CHẤT (%) của một mã danh mục hoá chất.
 *
 * Ngưỡng ở Phụ lục IV NĐ 24/2026 áp cho CHẤT TINH KHIẾT, nhưng hàng trong kho thường
 * là dung dịch (ví dụ H₂O₂ 30%). Khi đối chiếu tồn trữ với ngưỡng, khối lượng hoạt chất
 * quy đổi = tồn (kg) × ai_content_percent / 100.
 *
 * null  => coi như 100% (giữ nguyên hành vi cho tới khi có người khai).
 * Chụp thêm ở chemical_category_histories để không mất vết khi sửa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chemical_categories') && ! Schema::hasColumn('chemical_categories', 'ai_content_percent')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->decimal('ai_content_percent', 7, 4)->nullable()->after('density');
            });
        }

        if (Schema::hasTable('chemical_category_histories') && ! Schema::hasColumn('chemical_category_histories', 'ai_content_percent')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                $table->decimal('ai_content_percent', 7, 4)->nullable()->after('density');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chemical_categories') && Schema::hasColumn('chemical_categories', 'ai_content_percent')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->dropColumn('ai_content_percent');
            });
        }

        if (Schema::hasTable('chemical_category_histories') && Schema::hasColumn('chemical_category_histories', 'ai_content_percent')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                $table->dropColumn('ai_content_percent');
            });
        }
    }
};
