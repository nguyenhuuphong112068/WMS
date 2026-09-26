<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File đính kèm của phiếu dự trù vật tư (báo giá, catalogue, bản scan phiếu đã ký...).
 *
 * Xoá mềm bằng cột active (CFR Part 11): file gốc giữ nguyên trên đĩa để truy vết,
 * chỉ ẩn khỏi màn hình và ghi lại ai xoá, lúc nào.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_estimate_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_estimate_id');   // -> material_estimates.id
            $table->string('file_name');                           // Tên file gốc
            $table->string('file_path');                           // Đường dẫn trong storage
            $table->unsignedBigInteger('file_size')->nullable();   // bytes
            $table->string('file_type', 100)->nullable();          // MIME / đuôi file
            $table->string('note', 255)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->string('deleted_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('material_estimate_id', 'mat_est_att_estimate_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_estimate_attachments');
    }
};
