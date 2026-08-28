<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - LỊCH SỬ ĐIỀU CHỈNH: CHỤP THÊM PHÒNG BAN NHẬN
 *
 * exports có thêm loại 'transfer' (chuyển kho) kèm cột to_department_id, nên ảnh
 * chụp trong lịch sử cũng phải giữ giá trị này, nếu không xem lại một phiếu chuyển
 * sẽ không biết lúc đó đang chuyển cho phòng nào.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_export_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('to_department_id')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_export_histories', function (Blueprint $table) {
            $table->dropColumn('to_department_id');
        });
    }
};
