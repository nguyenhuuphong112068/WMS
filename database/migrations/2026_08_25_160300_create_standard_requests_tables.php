<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('request_lists')) {
            Schema::create('request_lists', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('group_id');
                $table->string('status', 20)->default('pending'); // pending, partial, completed, rejected
                $table->text('note')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index('department_id');
                $table->index('group_id');
            });
        }

        if (!Schema::hasTable('request_items')) {
            Schema::create('request_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_list_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('import_id')->nullable();
                $table->string('import_code', 50)->nullable();
                $table->decimal('requested_amount', 15, 4)->default(0);
                $table->string('requested_unit', 50)->nullable();
                $table->string('product_name', 255)->nullable();
                $table->unsignedBigInteger('analyst_id')->nullable();
                $table->decimal('issued_amount', 15, 4)->nullable();
                $table->string('issued_by', 100)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->string('status', 20)->default('pending'); // pending, issued, rejected
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index('request_list_id');
                $table->index('import_id');
                $table->index('analyst_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('request_items');
        Schema::dropIfExists('request_lists');
    }
};
