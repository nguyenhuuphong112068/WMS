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
        Schema::create('audittriallog', function (Blueprint $table) {
            $table->id();
            $table->string('userName')->nullable();
            $table->string('action')->nullable();
            $table->string('table_Audit')->nullable();
            $table->string('record_Id_AuditTrial')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audittriallog');
    }
};
