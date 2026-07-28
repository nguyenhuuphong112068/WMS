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
        // Phiếu theo dõi đường đi của hồ sơ
        Schema::create('document_routings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->foreignId('department_id')->nullable()->constrained('deparments')->nullOnDelete();

            // Bước 1: Văn thư khởi tạo
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('receiver_user_id')->nullable();
            $table->string('receiver_name', 150)->nullable();
            $table->date('received_date')->nullable();

            // Trạng thái luân chuyển: in_progress | completed | cancelled
            $table->string('status', 20)->default('in_progress');
            $table->unsignedInteger('current_step')->default(1);
            $table->unsignedBigInteger('current_holder_user_id')->nullable();
            $table->string('current_holder_name', 150)->nullable();

            // Bước 3: Kết thúc - vị trí lưu cuối cùng
            $table->foreignId('final_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_by', 150)->nullable();

            $table->text('note')->nullable();
            $table->string('created_by', 150)->nullable();
            $table->string('updated_by', 150)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('current_holder_user_id');
        });

        // Bước 2: các chặng chuyền tay (tuần tự)
        Schema::create('document_routing_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_id')->constrained('document_routings')->cascadeOnDelete();
            $table->unsignedInteger('step_no');

            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->string('from_user_name', 150)->nullable();

            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->string('to_user_name', 150)->nullable();
            $table->foreignId('to_department_id')->nullable()->constrained('deparments')->nullOnDelete();

            $table->date('handover_date')->nullable();   // ngày giao
            $table->date('received_date')->nullable();   // ngày người nhận xác nhận

            // pending = đã giao, chờ xác nhận | received = đã nhận
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->string('created_by', 150)->nullable();
            $table->timestamps();

            $table->index(['routing_id', 'step_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_routing_steps');
        Schema::dropIfExists('document_routings');
    }
};
