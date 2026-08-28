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
            $table->date('promised_date')->nullable()->after('expected_delivery_date');
        });
        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->date('promised_date')->nullable()->after('expected_delivery_date');
        });

        Schema::create('estimate_item_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('item_type'); // 'chemical' or 'standard'
            $table->string('user_name');
            $table->text('content');
            $table->string('type')->default('chat'); // 'chat' or 'system'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_item_chats');

        Schema::table('chemical_estimate_items', function (Blueprint $table) {
            $table->dropColumn('promised_date');
        });
        Schema::table('standard_estimate_items', function (Blueprint $table) {
            $table->dropColumn('promised_date');
        });
    }
};
