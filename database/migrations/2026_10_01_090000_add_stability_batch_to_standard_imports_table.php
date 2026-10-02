<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LÔ ĐÁNH GIÁ HẠN DÙNG
 *
 * Không phải ống chuẩn thứ cấp nào cũng đưa vào chương trình theo dõi độ ổn định -
 * phòng chỉ chọn một số lô làm đại diện. Người nhập tick cờ này ngay trên phiếu nhập,
 * và màn "Đánh Giá Hạn Dùng" chỉ cho chọn những ống đã tick để lập phiếu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->tinyInteger('stability_batch')->default(0)->after('requires_aliquot');
        });

        Schema::table('standard_import_histories', function (Blueprint $table) {
            $table->tinyInteger('stability_batch')->default(0)->after('requires_aliquot');
        });
    }

    public function down(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->dropColumn('stability_batch');
        });

        Schema::table('standard_import_histories', function (Blueprint $table) {
            $table->dropColumn('stability_batch');
        });
    }
};
