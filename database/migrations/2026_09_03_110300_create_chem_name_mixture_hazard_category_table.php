<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TÊN HOÁ CHẤT ↔ NHÓM NGUY HẠI BẢNG B (nhiều - nhiều).
 *
 * Người dùng tick các nhóm nguy hại của hỗn hợp ngay trên màn "Tên Hoá Chất".
 * Một hỗn hợp có gắn ít nhất một hoạt chất Bảng A và có ít nhất một dòng ở bảng
 * này thì bị xét theo Bảng B, đối chiếu tồn trữ với ngưỡng thấp nhất trong các
 * nhóm đã tick.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chem_name_mixture_hazard_category')) {
            return;
        }

        Schema::create('chem_name_mixture_hazard_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chem_names_id');
            $table->unsignedBigInteger('mixture_hazard_categories_id');
            $table->timestamps();

            $table->unique(['chem_names_id', 'mixture_hazard_categories_id'], 'cnmhc_pair_unique');
            $table->index('chem_names_id', 'cnmhc_chem_names_id_index');
            $table->index('mixture_hazard_categories_id', 'cnmhc_mhc_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chem_name_mixture_hazard_category');
    }
};
