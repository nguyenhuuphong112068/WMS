<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_name', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortName');
            $table->string('productType');
            $table->boolean('active')->default(true);
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_name');
    }
};
