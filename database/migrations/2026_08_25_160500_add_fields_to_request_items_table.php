<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('request_items', 'specification')) {
                $table->string('specification', 100)->nullable()->after('requested_unit');
            }
            if (!Schema::hasColumn('request_items', 'test_criteria')) {
                $table->string('test_criteria', 255)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('request_items', 'issued_unit')) {
                $table->string('issued_unit', 50)->nullable()->after('issued_amount');
            }
        });

        Schema::table('standard_exports', function (Blueprint $table) {
            if (!Schema::hasColumn('standard_exports', 'test_criteria')) {
                $table->string('test_criteria', 255)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('standard_exports', 'specification')) {
                $table->string('specification', 100)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('request_items', function (Blueprint $table) {
            $table->dropColumn(['specification', 'test_criteria', 'issued_unit']);
        });

        Schema::table('standard_exports', function (Blueprint $table) {
            $table->dropColumn(['test_criteria', 'specification']);
        });
    }
};
