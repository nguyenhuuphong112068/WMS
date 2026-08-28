<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - TỪ CHỐI NHẬN HÀNG CHUYỂN KHO
 *
 * Phòng nhận chưa nhận thì được TỪ CHỐI (thiếu hàng, sai hoá chất, bao bì hỏng...).
 * Từ chối sẽ khoá phiếu chuyển (status_id = 0) nên số lượng được trả lại tồn của
 * phòng gửi ngay, đồng thời ghi lại ai từ chối, lúc nào và vì sao.
 *
 * Phiếu đã bị từ chối thì KHÔNG mở khoá lại được - phòng gửi phải lập phiếu chuyển
 * mới, để không có chuyện mở lại một phiếu mà phòng kia đã nói là không nhận.
 *
 * Ngược lại, phiếu ĐÃ ĐƯỢC NHẬN (received_import_id khác NULL) thì khoá hoàn toàn:
 * không sửa, không khoá, không từ chối.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->dateTime('rejected_at')->nullable()->after('received_by');
            $table->string('rejected_by', 255)->nullable()->after('rejected_at');
            $table->string('reject_reason', 500)->nullable()->after('rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'rejected_by', 'reject_reason']);
        });
    }
};
