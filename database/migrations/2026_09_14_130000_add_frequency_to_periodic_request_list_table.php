<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tần suất đề nghị của danh sách NỘI BỘ theo chu kỳ: một mã tần suất của Đối tượng
 * (consumption_objects.frequency - Monthly, Quaterly, Half Yearly...). Lịch periodic +
 * cycle_length được suy ra từ mã này (MaterialPeriodicRequest::FREQUENCY_SCHEDULES).
 * Danh sách liên phòng ban để NULL - chu kỳ khai tay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodic_request_list', function (Blueprint $table) {
            if (! Schema::hasColumn('periodic_request_list', 'frequency')) {
                $table->string('frequency', 30)->nullable()->after('consumption_object_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodic_request_list', function (Blueprint $table) {
            if (Schema::hasColumn('periodic_request_list', 'frequency')) {
                $table->dropColumn('frequency');
            }
        });
    }
};
