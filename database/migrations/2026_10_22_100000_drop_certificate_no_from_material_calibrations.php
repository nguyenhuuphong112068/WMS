<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bỏ "Số giấy chứng nhận / biên bản hiệu chuẩn": QA chỉ xác nhận SỐ LƯỢNG ĐẠT từng mã xuất nhập
 * (MaterialCalibrationController::confirm), không ghi số chứng nhận.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('material_calibrations', 'certificate_no')) {
            Schema::table('material_calibrations', function (Blueprint $table) {
                $table->dropColumn('certificate_no');
            });
        }
    }

    public function down(): void
    {
        Schema::table('material_calibrations', function (Blueprint $table) {
            $table->string('certificate_no', 100)->nullable()->after('requested_at');
        });
    }
};
