<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỢT LẤY HÀNG VẬT TƯ (xuất kho thông minh)
 *
 * Lớp trung gian chèn vào giữa "đề nghị đã duyệt" và "hàng rời kho". Trước đây kho bấm
 * Cấp Phát là trừ tồn ngay dù hàng còn nằm trên kệ, nên không có khoảng nào để theo dõi
 * việc đi lấy hàng. Nay:
 *
 *      approved -> ĐỢT LẤY HÀNG -> Chờ xuất -> Đang lấy -> Đã lấy -> Đã đóng gói -> Đã xuất
 *                                                                                     |
 *                                              material_exports (TRỪ TỒN THẬT) <-------+
 *
 * GIỮ CHỖ TỒN: không có bảng giữ chỗ riêng. Chính dòng material_pick_lines đang treo LÀ
 * phần giữ chỗ, nên huỷ đợt hay bỏ dòng là tự nhả:
 *
 *      khả_dụng = amount + Σ material_balancings - Σ material_exports
 *                        - Σ material_pick_lines.suggested_amount (đợt và dòng còn treo)
 *
 * KHÔNG có cột chọn FIFO/FEFO. Chỉ một quy tắc duy nhất: sắp theo hạn dùng gần nhất
 * trước, lô KHÔNG có hạn dùng xếp sau và tự sắp theo ngày nhập - tức là vật tư có hạn
 * chạy FEFO, vật tư không hạn tự rơi về FIFO. Xem App\Support\MaterialPicking.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------- ĐỢT LẤY HÀNG (đầu phiếu) --------
        Schema::create('material_pick_waves', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();               // Mã đợt: DL-<mã phòng>-<đuôi ngẫu nhiên>, sinh ở Giai đoạn 2
            $table->unsignedBigInteger('department_id');        // -> deparments.id
            $table->string('name', 255)->nullable();            // Tên đợt do kho đặt: "Ca sáng 31/08 - Tổ Hoá lý"

            // new | picking | picked | packed | shipped | canceled - config('material.pick_wave_statuses')
            $table->string('status', 20)->default('new');

            $table->string('assigned_to', 100)->nullable();     // Nhân viên kho nhận đợt
            $table->timestamp('started_at')->nullable();        // Chờ xuất    -> Đang lấy
            $table->timestamp('picked_at')->nullable();         // Đang lấy    -> Đã lấy
            $table->timestamp('packed_at')->nullable();         // Đã lấy      -> Đã đóng gói
            $table->timestamp('shipped_at')->nullable();        // Đã đóng gói -> Đã xuất (trừ tồn)
            $table->string('canceled_by', 100)->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->string('note', 500)->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'material_pick_waves_department_id_index');
            $table->index('status', 'material_pick_waves_status_index');
        });

        /*
        | DÒNG LẤY HÀNG - một dòng = MỘT LÔ cho MỘT dòng đề nghị.
        |
        | Đây là chỗ cho phép tách một nhu cầu ra nhiều lô, thứ mà
        | material_request_items.import_id (một cột, một lô) không làm được.
        */
        Schema::create('material_pick_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wave_id');                  // -> material_pick_waves.id
            $table->unsignedBigInteger('request_item_id');          // -> material_request_items.id
            $table->unsignedBigInteger('request_list_id');          // -> material_request_lists.id (in Pick List khỏi join vòng)
            $table->unsignedBigInteger('category_id')->nullable();  // -> material_categories.id

            // Lô do engine đề xuất. Lưu cả mã / vị trí dạng chữ để trang in không phải join lại.
            $table->unsignedBigInteger('import_id');                // -> material_imports.id
            $table->string('import_code', 50)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();  // -> locations.id
            $table->string('location_code', 50)->nullable();
            $table->date('expired_date')->nullable();               // Chụp lại để tô cảnh báo cận hạn trên tờ in

            $table->unsignedInteger('sequence')->default(0);        // Thứ tự đi lấy: kho -> phòng -> kệ -> vị trí
            $table->decimal('suggested_amount', 15, 4)->default(0); // Engine đề xuất = phần ĐANG GIỮ
            $table->decimal('picked_amount', 15, 4)->nullable();    // Số nhân viên thực lấy được
            $table->string('unit', 50)->nullable();                 // Đơn vị của phòng lúc lập đợt

            // pending | picked | short | canceled - config('material.pick_line_statuses')
            $table->string('status', 20)->default('pending');

            $table->boolean('overridden')->default(false);          // Kho đổi khác lô engine gợi ý
            $table->string('picked_by', 100)->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->string('note', 500)->nullable();                // Lý do lấy thiếu / lý do đổi lô
            $table->timestamps();

            $table->index('wave_id', 'material_pick_lines_wave_id_index');
            $table->index('request_item_id', 'material_pick_lines_request_item_id_index');
            $table->index('import_id', 'material_pick_lines_import_id_index');
            $table->index('status', 'material_pick_lines_status_index');
        });

        // Truy ngược một dòng trừ tồn về đúng dòng Pick List đã sinh ra nó.
        // Nullable: phiếu cũ và phiếu lập thẳng (loại bỏ, cấp phát lẻ) không có dòng lấy hàng.
        Schema::table('material_exports', function (Blueprint $table) {
            $table->unsignedBigInteger('pick_line_id')->nullable()->after('request_item_id');
            $table->index('pick_line_id', 'material_exports_pick_line_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('material_exports', function (Blueprint $table) {
            $table->dropIndex('material_exports_pick_line_id_index');
            $table->dropColumn('pick_line_id');
        });

        Schema::dropIfExists('material_pick_lines');
        Schema::dropIfExists('material_pick_waves');
    }
};
