<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột EngshortName vào bảng deparments để lưu tên viết tắt tiếng Anh
 * của phòng ban (VD: QC, RD, PROC...). Cho phép bỏ trống.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deparments') && ! Schema::hasColumn('deparments', 'EngshortName')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->string('EngshortName', 50)->nullable()->after('shortName');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deparments') && Schema::hasColumn('deparments', 'EngshortName')) {
            Schema::table('deparments', function (Blueprint $table) {
                $table->dropColumn('EngshortName');
            });
        }
    }
};
