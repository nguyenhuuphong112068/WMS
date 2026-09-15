<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DANH SÁCH ĐỀ NGHỊ THEO CHU KỲ - NGÀY TẠO THEO LỊCH CAL
 *
 * Danh sách NỘI BỘ gắn một Đối tượng đồng bộ từ phần mềm CAL (consumption_objects.source = cal)
 * được chọn ngày tạo đề nghị theo ngày đến hạn (Sch_DueDate) của lịch Pending bên CAL, thay vì
 * một ngày cố định trong chu kỳ. Lịch tìm qua Inst_ID của thiết bị lớn + thiết bị con, xem
 * App\Support\MaterialPeriodicRequest::calPendingSchedules().
 *
 * - cycle_day_mode    : fixed = ngày cố định (cycle_day) | cal_due = theo Sch_DueDate lịch CAL
 * - cal_sch_id        : SCH_ID lịch Pending đang theo (next_run_date suy từ lịch này)
 * - cal_due_date      : Sch_DueDate của lịch đang theo
 * - last_cal_sch_id   : SCH_ID lịch đã dùng cho lần tự tạo đề nghị gần nhất
 * - last_cal_due_date : Sch_DueDate của lịch đó - mỗi chu kỳ chỉ tự tạo một đề nghị
 * - cal_checked_at    : lần gần nhất đọc lịch từ CAL
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('periodic_request_list', 'cycle_day_mode')) {
            return;
        }

        Schema::table('periodic_request_list', function (Blueprint $table) {
            $table->string('cycle_day_mode', 10)->default('fixed')->after('cycle_day');
            $table->unsignedBigInteger('cal_sch_id')->nullable()->after('frequency');
            $table->date('cal_due_date')->nullable()->after('cal_sch_id');
            $table->unsignedBigInteger('last_cal_sch_id')->nullable()->after('cal_due_date');
            $table->date('last_cal_due_date')->nullable()->after('last_cal_sch_id');
            $table->dateTime('cal_checked_at')->nullable()->after('last_cal_due_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('periodic_request_list', 'cycle_day_mode')) {
            return;
        }

        Schema::table('periodic_request_list', function (Blueprint $table) {
            $table->dropColumn(['cycle_day_mode', 'cal_sch_id', 'cal_due_date', 'last_cal_sch_id', 'last_cal_due_date', 'cal_checked_at']);
        });
    }
};
