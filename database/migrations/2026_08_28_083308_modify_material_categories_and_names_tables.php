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
        Schema::table('material_names', function (Blueprint $table) {
            if (Schema::hasColumn('material_names', 'technical_information')) {
                $table->dropColumn('technical_information');
            }
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropUnique('material_categories_combo_unique');
            $table->dropColumn('suppliers_id');
            $table->string('technical_specification', 100)->nullable();
            $table->double('min_stock', 15, 4)->nullable();
            $table->unique(['material_names_id', 'manufacturers_id', 'unit_id'], 'material_categories_combo_unique');
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->dropColumn('suppliers_id');
            $table->string('technical_specification', 100)->nullable();
            $table->double('min_stock', 15, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_names', function (Blueprint $table) {
            $table->text('technical_information')->nullable();
        });

        Schema::table('material_categories', function (Blueprint $table) {
            $table->bigInteger('suppliers_id')->nullable();
            $table->dropColumn(['technical_specification', 'min_stock']);
        });

        Schema::table('material_category_histories', function (Blueprint $table) {
            $table->bigInteger('suppliers_id')->nullable();
            $table->dropColumn(['technical_specification', 'min_stock']);
        });
    }
};
