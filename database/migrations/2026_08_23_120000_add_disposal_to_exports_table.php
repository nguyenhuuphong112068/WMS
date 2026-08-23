<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - HUỶ BỎ HOÁ CHẤT THEO HAI BƯỚC
 *
 * Phiếu huỷ bỏ (exports.type = 'cancel') không huỷ ngay khi lập. Nghiệp vụ chia đôi:
 *
 * BƯỚC 1 - LOẠI BỎ : lập phiếu huỷ bỏ, hoá chất bị đánh dấu loại bỏ và trừ tồn ngay.
 *                    Phiếu rơi vào tab "Hoá chất chờ huỷ" (disposal_id còn NULL).
 * BƯỚC 2 - HUỶ     : gom nhiều phiếu loại bỏ thành MỘT đợt huỷ (bảng disposals) để
 *                    xin quyết định huỷ một lần từ TP. ĐBCL và Ban Giám Đốc.
 *
 * test_report_no : Số Phiếu KN, OOS, BCSL... - căn cứ cho việc loại bỏ, in ra cột
 *                  cùng tên ở mục 1 "Tổng kết phế phẩm" của biểu mẫu QA/F/058-07.
 * disposal_id    : -> disposals.id. Còn NULL nghĩa là chưa gom vào đợt nào, đang nằm
 *                  ở hàng chờ huỷ. Đã có giá trị thì phiếu bị khoá, muốn sửa phải gỡ
 *                  khỏi đợt trước - số lượng trên phiếu chính là số lượng đã ghi vào
 *                  hồ sơ xin quyết định huỷ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->string('test_report_no', 100)->nullable()->after('purpose');
            $table->unsignedBigInteger('disposal_id')->nullable()->after('test_report_no');

            $table->index('disposal_id', 'exports_disposal_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->dropIndex('exports_disposal_id_index');
            $table->dropColumn(['test_report_no', 'disposal_id']);
        });
    }
};
