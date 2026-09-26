<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Huỷ mục dự trù hoá chất / chất chuẩn cần xác nhận của HAI BÊN (phòng đề nghị + bộ phận
 * mua hàng) - cùng cơ chế với material_estimate_items, xem EstimateItemCancel.
 */
return new class extends Migration
{
    private const TABLES = ['chemical_estimate_items', 'standard_estimate_items'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasColumn($name, 'cancel_requester_by')) {
                continue;
            }

            Schema::table($name, function (Blueprint $table) {
                $table->string('cancel_requester_by')->nullable()->after('cancel_reason');
                $table->timestamp('cancel_requester_at')->nullable()->after('cancel_requester_by');
                $table->string('cancel_purchasing_by')->nullable()->after('cancel_requester_at');
                $table->timestamp('cancel_purchasing_at')->nullable()->after('cancel_purchasing_by');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasColumn($name, 'cancel_requester_by')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropColumn(['cancel_requester_by', 'cancel_requester_at', 'cancel_purchasing_by', 'cancel_purchasing_at']);
                });
            }
        }
    }
};
