<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('activity_type')->nullable();
                $table->text('message')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('url')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('sender_id');
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('notification_recipients')) {
            Schema::create('notification_recipients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('notification_id');
                $table->unsignedBigInteger('user_id');
                $table->tinyInteger('is_read')->default(0);
                $table->timestamp('read_at')->nullable();

                $table->index(['user_id', 'is_read']);
                $table->index('notification_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
    }
};
