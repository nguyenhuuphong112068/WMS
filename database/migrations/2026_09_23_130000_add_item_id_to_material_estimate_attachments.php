<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File đính kèm theo TỪNG VẬT TƯ dự trù (báo giá, ảnh, catalogue của riêng vật tư đó).
 * material_estimate_item_id = NULL là file chung của cả phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('material_estimate_attachments', 'material_estimate_item_id')) {
            Schema::table('material_estimate_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('material_estimate_item_id')->nullable()->after('material_estimate_id');
                $table->index('material_estimate_item_id', 'mat_est_att_item_id_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('material_estimate_attachments', 'material_estimate_item_id')) {
            Schema::table('material_estimate_attachments', function (Blueprint $table) {
                $table->dropIndex('mat_est_att_item_id_index');
                $table->dropColumn('material_estimate_item_id');
            });
        }
    }
};
