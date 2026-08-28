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
        Schema::table('chemical_estimates', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('reject_reason');
        });
        Schema::table('chemical_estimate_items', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('fulfilled_date');
        });
        Schema::table('standard_estimates', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('reject_reason');
        });
        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('fulfilled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chemical_estimates', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
        Schema::table('chemical_estimate_items', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
        Schema::table('standard_estimates', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
