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
        Schema::table('material_categories', function (Blueprint $table) {
            $table->string('classification', 10)->nullable();
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->string('classification', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn('classification');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn('classification');
        });
    }
};
