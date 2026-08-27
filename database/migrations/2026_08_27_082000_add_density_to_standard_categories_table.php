<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standard_categories')) {
            Schema::table('standard_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('standard_categories', 'density')) {
                    $table->decimal('density', 10, 4)->nullable()->after('unit_id'); // Tỷ trọng d (g/ml)
                }
            });
        }

        if (Schema::hasTable('standard_category_histories')) {
            Schema::table('standard_category_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('standard_category_histories', 'density')) {
                    $table->decimal('density', 10, 4)->nullable()->after('unit_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_categories')) {
            Schema::table('standard_categories', function (Blueprint $table) {
                if (Schema::hasColumn('standard_categories', 'density')) {
                    $table->dropColumn('density');
                }
            });
        }

        if (Schema::hasTable('standard_category_histories')) {
            Schema::table('standard_category_histories', function (Blueprint $table) {
                if (Schema::hasColumn('standard_category_histories', 'density')) {
                    $table->dropColumn('density');
                }
            });
        }
    }
};
