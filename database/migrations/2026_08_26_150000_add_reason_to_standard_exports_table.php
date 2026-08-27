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
                if (!Schema::hasColumn('standard_exports', 'reason')) {
                    $table->string('reason', 500)->nullable()->after('testing');
                }
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('standard_export_histories', 'reason')) {
                    $table->string('reason', 500)->nullable()->after('testing');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                if (Schema::hasColumn('standard_exports', 'reason')) {
                    $table->dropColumn('reason');
                }
            });
        }

        if (Schema::hasTable('standard_export_histories')) {
            Schema::table('standard_export_histories', function (Blueprint $table) {
                if (Schema::hasColumn('standard_export_histories', 'reason')) {
                    $table->dropColumn('reason');
                }
            });
        }
    }
};
