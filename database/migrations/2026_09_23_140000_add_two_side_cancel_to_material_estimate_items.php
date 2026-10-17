<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Huỷ mục dự trù vật tư cần XÁC NHẬN CỦA HAI BÊN: phòng đề nghị và bộ phận mua hàng.
 *
 * Bên nào bấm trước thì ghi *_by/*_at của bên đó, mục chuyển "Chờ xác nhận huỷ"
 * (status_id vẫn 1). Khi đủ cả hai bên mới đặt status_id = 0 (đã huỷ).
 * Bên còn lại từ chối thì xoá cả hai cặp cột, mục quay lại bình thường.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_estimate_items', function (Blueprint $table) {
            if (! Schema::hasColumn('material_estimate_items', 'cancel_requester_by')) {
                $table->string('cancel_requester_by')->nullable()->after('cancel_reason');
                $table->timestamp('cancel_requester_at')->nullable()->after('cancel_requester_by');
                $table->string('cancel_purchasing_by')->nullable()->after('cancel_requester_at');
                $table->timestamp('cancel_purchasing_at')->nullable()->after('cancel_purchasing_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_estimate_items', function (Blueprint $table) {
            $table->dropColumn(['cancel_requester_by', 'cancel_requester_at', 'cancel_purchasing_by', 'cancel_purchasing_at']);
        });
    }
};
