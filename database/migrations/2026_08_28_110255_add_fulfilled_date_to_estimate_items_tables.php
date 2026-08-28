<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chemical_estimate_items', function (Blueprint $table) {
            $table->date('fulfilled_date')->nullable()->after('expected_delivery_date');
        });

        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->date('fulfilled_date')->nullable()->after('expected_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chemical_estimate_items', function (Blueprint $table) {
            $table->dropColumn('fulfilled_date');
        });

        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->dropColumn('fulfilled_date');
        });
    }
};
