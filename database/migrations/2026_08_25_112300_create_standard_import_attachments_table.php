<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_import_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('standard_import_id');
            $table->string('file_name');                                // Tên file hiển thị (original name)
            $table->string('file_path');                                // Đường dẫn lưu trữ (relative storage path)
            $table->unsignedBigInteger('file_size')->nullable();        // Dung lượng (bytes)
            $table->string('file_type', 100)->nullable();               // MIME type hoặc extension
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('standard_import_id', 'std_imp_att_import_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_import_attachments');
    }
};
