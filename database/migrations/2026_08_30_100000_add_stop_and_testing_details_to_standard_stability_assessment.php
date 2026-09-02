<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐÁNH GIÁ HẠN DÙNG - NGƯNG ĐÁNH GIÁ GIỮA CHỪNG + CHI TIẾT TỪNG CHỈ TIÊU
 *
 * 1. NGƯNG ĐÁNH GIÁ (bảng ..._list)
 *
 * Một phiếu có nhiều mốc kiểm nối tiếp nhau, nhưng không phải lúc nào cũng chạy hết:
 *
 *      - Mốc nào kết luận "Không Đạt" thì chất chuẩn đã hỏng, các mốc sau không còn ý
 *        nghĩa nên phiếu dừng ngay tại đó.
 *      - Mốc "Đạt" thì người dùng chọn: đánh giá tiếp, hoặc ngưng vì một lý do khác
 *        (dùng hết ống, đổi chuẩn, ngừng sản xuất...) - lý do bắt buộc phải nhập.
 *
 * Ba cột dưới đây ghi lại việc ngưng đó. Trạng thái phiếu lúc này là "Dừng Đánh Giá",
 * khác với "Huỷ" (huỷ là bỏ cả phiếu, dừng là đã đánh giá được một phần rồi ngưng).
 * Phiếu dừng vẫn mở lại được nên ba cột đều nullable, mở lại thì xoá trắng.
 *
 * 2. CHI TIẾT TỪNG CHỈ TIÊU (bảng ..._item)
 *
 * Cột testings trước đây chỉ là mảng TÊN chỉ tiêu: ["Định tính","Định lượng"]. Nay mỗi
 * chỉ tiêu còn phải đánh dấu ĐÃ CẤP PHÁT CHUẨN cho người kiểm hay chưa, kèm ghi chú
 * riêng, nên mỗi phần tử thành một object:
 *
 *      [{"name":"Định tính","issued":true,"note":"Cấp 2 ống ngày 30/08"}, ...]
 *
 * Vẫn giữ JSON trong một cột thay vì tách bảng con: đây là nội dung của đúng một mốc,
 * không tra cứu chéo, tách ra chỉ thêm một bảng và một loạt join. Nhưng varchar(500)
 * không đủ chứa thêm ghi chú của tối đa 20 chỉ tiêu nên đổi sang TEXT.
 *
 * Dữ liệu cũ dạng mảng chuỗi vẫn đọc được: phần đọc JSON ở Controller nhận cả hai kiểu
 * và coi chuỗi trần là chỉ tiêu chưa cấp phát, chưa có ghi chú - nên migration này
 * KHÔNG cần chuyển đổi dữ liệu, cứ để nguyên, ghi lại lần sau là tự lên format mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_stability_assessment_list', function (Blueprint $table) {
            $table->string('stop_reason', 255)->nullable()->after('status');   // Lý do ngưng đánh giá
            $table->timestamp('stopped_at')->nullable()->after('stop_reason'); // Thời điểm bấm ngưng
            $table->string('stopped_by')->nullable()->after('stopped_at');     // Người bấm ngưng
        });

        Schema::table('standard_stability_assessment_item', function (Blueprint $table) {
            $table->text('testings')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('standard_stability_assessment_list', function (Blueprint $table) {
            $table->dropColumn(['stop_reason', 'stopped_at', 'stopped_by']);
        });

        Schema::table('standard_stability_assessment_item', function (Blueprint $table) {
            $table->string('testings', 500)->nullable()->change();
        });
    }
};
