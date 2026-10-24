<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU TRẢ VỀ CỦA PHIẾU CÁCH LY VẬT TƯ HỎNG
 *
 *   returned_location_id : vị trí đặt lại vật tư khi quyết định "Trả về kho" (bước 2) - mặc định là
 *                          vị trí của lô trước khi cách ly. Cách ly cả lô mà đổi sang vị trí khác thì
 *                          material_imports.location_id cũng đổi theo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_quarantines', function (Blueprint $table) {
            $table->unsignedBigInteger('returned_location_id')->nullable()->after('rejected_location_id'); // -> locations.id
        });
    }

    public function down(): void
    {
        Schema::table('material_quarantines', function (Blueprint $table) {
            $table->dropColumn('returned_location_id');
        });
    }
};
