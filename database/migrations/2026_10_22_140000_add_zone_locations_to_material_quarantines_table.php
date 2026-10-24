<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU CỦA PHIẾU CÁCH LY VẬT TƯ HỎNG
 *
 *   pending_location_id  : vị trí zone_type = 'pending' (Chờ Quyết Định) - chọn ở bước 1 Cách ly
 *   rejected_location_id : vị trí zone_type = 'rejected' (Loại Bỏ/Chờ Huỷ) - chọn ở bước 2 khi
 *                          quyết định Loại bỏ
 *
 * Lưu trên phiếu cách ly chứ không đổi material_imports.location_id: phiếu có thể chỉ cách ly
 * một phần lô, phần còn lại vẫn nằm ở vị trí cũ. Trả về kho thì hàng quay lại vị trí của lô.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_quarantines', function (Blueprint $table) {
            $table->unsignedBigInteger('pending_location_id')->nullable()->after('reason');     // -> locations.id
            $table->unsignedBigInteger('rejected_location_id')->nullable()->after('decision_note'); // -> locations.id
        });
    }

    public function down(): void
    {
        Schema::table('material_quarantines', function (Blueprint $table) {
            $table->dropColumn(['pending_location_id', 'rejected_location_id']);
        });
    }
};
