<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_import_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chemical_import_id');         // -> chemical_imports.id
            $table->string('file_name');                               // Tên file hiển thị (original name)
            $table->string('file_path');                               // Đường dẫn lưu trữ (relative storage path)
            $table->unsignedBigInteger('file_size')->nullable();       // Dung lượng (bytes)
            $table->string('file_type', 100)->nullable();              // MIME type hoặc extension
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('chemical_import_id', 'chem_imp_att_import_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_import_attachments');
    }
};
