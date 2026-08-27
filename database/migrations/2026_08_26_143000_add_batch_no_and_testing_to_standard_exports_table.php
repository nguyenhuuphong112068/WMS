<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                if (!Schema::hasColumn('standard_exports', 'batch_no')) {
                    $table->string('batch_no', 100)->nullable()->after('product_name');
                }
                if (!Schema::hasColumn('standard_exports', 'testing')) {
                    $table->string('testing', 255)->nullable()->after('batch_no');
                }
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('standard_export_histories', 'product_name')) {
                    $table->string('product_name', 255)->nullable()->after('purpose');
                }
                if (!Schema::hasColumn('standard_export_histories', 'batch_no')) {
                    $table->string('batch_no', 100)->nullable()->after('product_name');
                }
                if (!Schema::hasColumn('standard_export_histories', 'testing')) {
                    $table->string('testing', 255)->nullable()->after('batch_no');
                }
                if (!Schema::hasColumn('standard_export_histories', 'test_criteria')) {
                    $table->string('test_criteria', 255)->nullable()->after('testing');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                if (Schema::hasColumn('standard_exports', 'testing')) {
                    $table->dropColumn('testing');
                }
                if (Schema::hasColumn('standard_exports', 'batch_no')) {
                    $table->dropColumn('batch_no');
                }
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                if (Schema::hasColumn('standard_export_histories', 'test_criteria')) {
                    $table->dropColumn('test_criteria');
                }
                if (Schema::hasColumn('standard_export_histories', 'testing')) {
                    $table->dropColumn('testing');
                }
                if (Schema::hasColumn('standard_export_histories', 'batch_no')) {
                    $table->dropColumn('batch_no');
                }
                if (Schema::hasColumn('standard_export_histories', 'product_name')) {
                    $table->dropColumn('product_name');
                }
            });
        }
    }
};
