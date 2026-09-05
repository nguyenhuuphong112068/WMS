<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - TÁCH BƯỚC "PHÒNG NHẬN BẤM NHẬN" RA KHỎI BƯỚC CẤP PHÁT
 *
 * Cơ chế 1 bước (rebuild_chemical_transfer_requests / create_standard_transfer_tables)
 * cho B cấp phát là tạo tồn A ngay. Đổi lại thành 3 bước: B cấp phát chỉ trừ tồn B (item
 * chuyển sang status='issued' - CHỜ NHẬN, chưa có dòng imports nào cho A); A tự vào tab
 * "Đề nghị liên phòng ban" chọn vị trí lưu của phòng mình rồi bấm Nhận thì mới thật sự
 * tạo dòng chemical_imports/standard_imports cho A (status chuyển 'received'). A cũng có
 * thể Từ chối nhận (status 'returned') để hoàn tồn lại cho B - set status_id=0 trên dòng
 * export transfer_out tương ứng, không cần cột riêng vì remaining() chỉ cộng status_id=1.
 *
 * is_partial_lot: chemical_imports.is_partial_lot (LÔ NGUYÊN/LÔ LẺ, xem migration
 * add_is_partial_lot_to_imports_table) phải chốt NGAY LÚC B CẤP PHÁT (dựa vào tình trạng
 * lô nguồn tại thời điểm đó: đã bị cân đối/xuất bớt lần nào chưa) - không thể tính lại lúc
 * A nhận vì lô nguồn có thể phát sinh giao dịch khác trong lúc chờ nhận. Nên chụp tạm ở
 * đây rồi dùng lại khi tạo chemical_imports thật ở bước Nhận. Standard không có khái niệm
 * lô nguyên/lô lẻ nên standard_transfer_items không cần cột này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_transfer_items', function (Blueprint $table) {
            $table->boolean('is_partial_lot')->nullable()->after('issued_at');
            $table->string('received_by', 255)->nullable()->after('new_import_id');
            $table->timestamp('received_at')->nullable()->after('received_by');
            $table->string('return_note', 500)->nullable()->after('received_at');
            $table->string('returned_by', 255)->nullable()->after('return_note');
            $table->timestamp('returned_at')->nullable()->after('returned_by');
        });

        Schema::table('standard_transfer_items', function (Blueprint $table) {
            $table->string('received_by', 255)->nullable()->after('new_import_id');
            $table->timestamp('received_at')->nullable()->after('received_by');
            $table->string('return_note', 500)->nullable()->after('received_at');
            $table->string('returned_by', 255)->nullable()->after('return_note');
            $table->timestamp('returned_at')->nullable()->after('returned_by');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['is_partial_lot', 'received_by', 'received_at', 'return_note', 'returned_by', 'returned_at']);
        });

        Schema::table('standard_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['received_by', 'received_at', 'return_note', 'returned_by', 'returned_at']);
        });
    }
};
