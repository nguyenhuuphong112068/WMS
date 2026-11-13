<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phân loại vật tư "HC sau cấp phát" (qa_calibration = after): cấp phát bình thường, mỗi lần cấp
 * tự sinh phiếu hiệu chuẩn gửi QA xác nhận. Phiếu loại này KHÔNG giữ chỗ tồn (hàng đã cấp).
 *
 *   timing    : before = hiệu chuẩn trước cấp phát (luồng cũ) | after = sau cấp phát
 *   export_id : phiếu sử dụng (material_exports) sinh ra lúc cấp - chỉ có ở timing = after
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_calibrations', function (Blueprint $table) {
            $table->string('timing', 10)->default('before')->after('code');
            $table->unsignedBigInteger('export_id')->nullable()->after('import_id');
            $table->index('timing');
        });
    }

    public function down(): void
    {
        Schema::table('material_calibrations', function (Blueprint $table) {
            $table->dropIndex(['timing']);
            $table->dropColumn(['timing', 'export_id']);
        });
    }
};
