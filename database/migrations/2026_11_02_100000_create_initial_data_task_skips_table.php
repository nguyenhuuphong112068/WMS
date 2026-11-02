<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Việc khởi tạo dữ liệu Admin đánh dấu "Không áp dụng" trên bảng theo dõi (VD phòng không dùng
 * chất chuẩn thì bỏ việc GĐ3 / GĐ4 - Chất chuẩn của phòng đó). Việc = giai đoạn + module + phòng
 * ban; dòng tồn tại = đang bỏ qua, xoá dòng = áp dụng lại.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initial_data_task_skips', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('stage');
            $table->string('module', 20)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['stage', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initial_data_task_skips');
    }
};
