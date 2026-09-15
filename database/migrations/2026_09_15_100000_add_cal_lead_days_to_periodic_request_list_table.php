<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Theo ngày đến hạn lịch CAL" (xem 2026_09_15_090000_...): cho tạo đề nghị TRƯỚC hạn CAL vài
 * ngày để chuẩn bị vật tư kịp - cal_lead_days trừ vào Sch_DueDate khi tính next_run_date
 * (App\Support\MaterialPeriodicRequest::calNextSchedule()), không lùi trước start_date.
 * Mặc định 3 ngày. Không dùng khi cycle_day_mode = fixed (bỏ qua, không xoá giá trị đang có).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('periodic_request_list', 'cal_lead_days')) {
            return;
        }

        Schema::table('periodic_request_list', function (Blueprint $table) {
            $table->unsignedTinyInteger('cal_lead_days')->default(3)->after('cycle_day_mode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('periodic_request_list', 'cal_lead_days')) {
            return;
        }

        Schema::table('periodic_request_list', function (Blueprint $table) {
            $table->dropColumn('cal_lead_days');
        });
    }
};
