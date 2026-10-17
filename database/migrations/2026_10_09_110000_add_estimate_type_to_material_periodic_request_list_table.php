<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DANH SÁCH DỰ TRÙ VẬT TƯ THEO CHU KỲ
 *
 * Thêm loại thứ 3 cho material_periodic_request_list: type = estimate. Đến hạn hệ thống tự
 * lập một phiếu dự trù vật tư (material_estimates, Nháp) thay vì đề nghị cấp phát - xem
 * App\Support\MaterialPeriodicRequest::createEstimate().
 *
 * estimate_month_offset: phiếu dự trù cho tháng nào, tính từ tháng của ngày tạo phiếu
 * (0 = chính tháng đó, 1 = tháng sau...). Chỉ dùng cho type = estimate.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE material_periodic_request_list MODIFY type ENUM('internal','external','estimate') NOT NULL DEFAULT 'internal'");

        Schema::table('material_periodic_request_list', function (Blueprint $table) {
            $table->unsignedTinyInteger('estimate_month_offset')->nullable()->after('to_department_id');
        });
    }

    public function down(): void
    {
        DB::table('material_periodic_request_list')->where('type', 'estimate')->update(['type' => 'internal', 'status_id' => 0]);

        Schema::table('material_periodic_request_list', function (Blueprint $table) {
            $table->dropColumn('estimate_month_offset');
        });

        DB::statement("ALTER TABLE material_periodic_request_list MODIFY type ENUM('internal','external') NOT NULL DEFAULT 'internal'");
    }
};
