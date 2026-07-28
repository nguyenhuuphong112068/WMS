<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_management', function (Blueprint $table) {
            $table->id();
            $table->string('userName')->unique();
            $table->string('passWord');
            $table->string('fullName')->nullable();
            $table->string('userGroup')->nullable();
            $table->string('deparment')->nullable();
            $table->string('groupName')->nullable();
            $table->string('mail')->nullable();
            $table->timestamp('changePWdate')->nullable();
            $table->string('prepareBy')->nullable();
            $table->boolean('isActive')->default(true);
            $table->integer('last_activity')->nullable();
            $table->timestamps();
        });
        
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        
        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('user_management');
    }
};
