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
        Schema::create('standard_names', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                           // Tên chuẩn
            $table->string('active_ingredient_name')->nullable();       // Tên hoạt chất
            $table->string('cas_no', 100)->nullable();                  // Số CAS
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_names');
    }
};
