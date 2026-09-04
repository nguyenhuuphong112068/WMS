<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nới cột active_ingredients.note từ VARCHAR(255) sang TEXT.
 *
 * Ghi chú luật định của Bảng A Phụ lục IV NĐ 24/2026/NĐ-CP có dòng dài hơn 255 ký tự
 * (ví dụ STT 271 liệt kê cả nhóm chất gây ung thư, STT 8 / 135 mô tả ngưỡng theo % hỗn hợp).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('active_ingredients', 'note')) {
            return;
        }

        Schema::table('active_ingredients', function (Blueprint $table) {
            $table->text('note')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('active_ingredients', 'note')) {
            return;
        }

        Schema::table('active_ingredients', function (Blueprint $table) {
            $table->string('note', 255)->nullable()->change();
        });
    }
};
