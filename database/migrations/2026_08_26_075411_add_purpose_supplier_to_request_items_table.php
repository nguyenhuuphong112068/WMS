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
        Schema::table('request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('request_items', 'purpose_id')) {
                $table->unsignedBigInteger('purpose_id')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('request_items', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('purpose_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_items', function (Blueprint $table) {
            $table->dropColumn(['purpose_id', 'supplier_id']);
        });
    }
};
