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
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->string('weight_deviation_remark', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->dropColumn('weight_deviation_remark');
        });
    }
};
