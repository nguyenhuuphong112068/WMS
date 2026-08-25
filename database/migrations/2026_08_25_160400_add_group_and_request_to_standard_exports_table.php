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
                if (!Schema::hasColumn('standard_exports', 'group_id')) {
                    $table->unsignedBigInteger('group_id')->nullable()->after('department_id');
                }
                if (!Schema::hasColumn('standard_exports', 'request_item_id')) {
                    $table->unsignedBigInteger('request_item_id')->nullable()->after('import_id');
                }
                if (!Schema::hasColumn('standard_exports', 'product_name')) {
                    $table->string('product_name', 255)->nullable()->after('purpose');
                }
                if (!Schema::hasColumn('standard_exports', 'analyst_id')) {
                    $table->unsignedBigInteger('analyst_id')->nullable()->after('product_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                if (Schema::hasColumn('standard_exports', 'analyst_id')) {
                    $table->dropColumn('analyst_id');
                }
                if (Schema::hasColumn('standard_exports', 'product_name')) {
                    $table->dropColumn('product_name');
                }
                if (Schema::hasColumn('standard_exports', 'request_item_id')) {
                    $table->dropColumn('request_item_id');
                }
                if (Schema::hasColumn('standard_exports', 'group_id')) {
                    $table->dropColumn('group_id');
                }
            });
        }
    }
};
