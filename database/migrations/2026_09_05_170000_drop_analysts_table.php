<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('analysts');
    }

    public function down(): void
    {
        if (!Schema::hasTable('analysts')) {
            Schema::create('analysts', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->tinyInteger('status_id')->default(1);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }
};
