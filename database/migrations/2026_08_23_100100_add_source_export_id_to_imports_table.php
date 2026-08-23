<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - ĐÁNH DẤU LÔ NHẬN TỪ PHÒNG BAN KHÁC
 *
 * source_export_id : -> exports.id của phiếu chuyển kho đã sinh ra dòng nhập này.
 *                    NULL = nhập từ ngoài vào (mua của nhà cung cấp) như bình thường.
 *                    Có giá trị = nhận từ phòng ban khác chuyển sang; phòng gửi,
 *                    ngày chuyển, người chuyển tra ngược qua exports.
 *
 * Lô nhận từ phòng khác KHÔNG có số hoá đơn / ngày hoá đơn (không mua từ ngoài),
 * và mã của nó giữ nguyên mã của phòng nhập đầu tiên kèm hậu tố -CK<số thứ tự>.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->unsignedBigInteger('source_export_id')->nullable()->after('supplier_id');

            $table->index('source_export_id', 'imports_source_export_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropIndex('imports_source_export_id_index');
            $table->dropColumn('source_export_id');
        });
    }
};
