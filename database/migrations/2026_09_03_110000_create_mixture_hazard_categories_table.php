<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - NHÓM NGUY HẠI BẢNG B (Phụ lục IV Nghị định 24/2026/NĐ-CP)
 *
 * Bảng B phân loại hỗn hợp hoá chất theo NHÓM nguy hại GHS (Nguy hại sức khỏe /
 * Nguy hại vật chất / Nguy hại cho môi trường / Nguy hại khác), mỗi nhóm có
 * "Ngưỡng khối lượng hoá chất tồn trữ lớn nhất tại một thời điểm (kg)" riêng.
 *
 * Một hỗn hợp (chem_names) chứa ít nhất một hoạt chất thuộc Bảng A và được tick
 * một hay nhiều nhóm ở bảng này thì bị xét theo Bảng B; đối chiếu tổng tồn trữ
 * toàn công ty với ngưỡng THẤP NHẤT trong các nhóm đã tick.
 *
 * Có luồng duyệt như active_ingredients để bổ sung / chỉnh ngưỡng khi văn bản đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mixture_hazard_categories')) {
            return;
        }

        Schema::create('mixture_hazard_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();               // Mã tự sinh B00001
            $table->string('hazard_group', 5);                   // I | II | III | IV
            $table->unsignedSmallInteger('ordinal');             // STT trong nhóm
            $table->string('name', 1000);                        // Mô tả nhóm phân loại (có thể nhiều dòng)
            $table->decimal('threshold_kg', 15, 3);              // Ngưỡng tồn trữ (kg) - Bảng B luôn có ngưỡng
            $table->string('threshold_basis', 20)->nullable();   // 'net' cho các dòng ghi "(net)"
            $table->string('legal_ref', 255)->default('Nghị định 24/2026/NĐ-CP - Phụ lục IV - Bảng B');
            $table->tinyInteger('is_statutory')->default(1);     // 1 = từ văn bản luật
            $table->string('note', 255)->nullable();
            $table->string('app_status', 20)->default('pending');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['hazard_group', 'ordinal'], 'mhc_group_ordinal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixture_hazard_categories');
    }
};
