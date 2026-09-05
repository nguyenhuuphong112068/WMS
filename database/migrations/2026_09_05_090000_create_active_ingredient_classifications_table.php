<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHÂN LOẠI HOẠT CHẤT THEO NGHỊ ĐỊNH 24/2026/NĐ-CP (quy tắc "hình 1").
 *
 * Một hoạt chất (active_ingredients) có thể nằm trong NHIỀU nhóm cùng lúc (ví dụ vừa
 * Phụ lục III bảng A, vừa Phụ lục IV bảng A) nên phân loại tách thành bảng con, mỗi
 * dòng là một bộ (phụ lục / nhóm / bảng).
 *
 *   appendix   : 'II' | 'III' | 'IV'   - phụ lục của Nghị định
 *   group_no   : 1 | 2 | null          - "nhóm 1" / "nhóm 2" trong phụ lục (PL IV không chia nhóm)
 *   table_ref  : 'A' | 'B' | 'C' | null - bảng A / B / C (PL II không chia bảng)
 *
 * Ánh xạ sang số nhóm 1..10 của hình 1 do App\Support\ChemicalClassification::groupOf() lo:
 *   (II,1,-)   => 1   (III,1,A) => 3   (III,1,B) => 4
 *   (III,2,A)  => 5   (III,2,B) => 6   (III,2,C) => 7   (IV,-,A) => 9
 *
 * Các nhóm chỉ dành cho HỖN HỢP (2, 8, 10) không khai ở đây mà suy ở màn "Tên Hoá Chất".
 *
 * is_statutory : 1 = dòng lấy từ nghị định (seed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('active_ingredient_classifications')) {
            return;
        }

        Schema::create('active_ingredient_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('active_ingredients_id');
            $table->string('appendix', 4);                       // II | III | IV
            $table->unsignedTinyInteger('group_no')->nullable(); // 1 | 2 | null
            $table->string('table_ref', 1)->nullable();          // A | B | C | null
            $table->string('note', 255)->nullable();
            $table->tinyInteger('is_statutory')->default(1);
            $table->timestamps();

            $table->unique(
                ['active_ingredients_id', 'appendix', 'group_no', 'table_ref'],
                'aic_pair_unique'
            );
            $table->index('active_ingredients_id', 'aic_active_ingredients_id_index');
            $table->index(['appendix', 'table_ref'], 'aic_appendix_table_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_ingredient_classifications');
    }
};
