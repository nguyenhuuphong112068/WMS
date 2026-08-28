<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - PHÂN BIỆT LÔ NGUYÊN VÀ LÔ LẺ
 *
 * is_partial_lot = 0 : LÔ NGUYÊN. Nhập từ ngoài vào, hoặc nhận nguyên cả lô từ phòng
 *                      ban khác. Nhận nguyên phải đủ BA điều kiện ở lô nguồn:
 *                        - lượng chuyển = imports.amount (LƯỢNG NHẬP GỐC, không cộng cân đối)
 *                        - lô chưa cân đối lần nào
 *                        - lô chưa xuất ra lần nào khác
 *                      Lô còn y nguyên nên vẫn có hao hụt cân đong: được xuất vượt tồn
 *                      5% và được cân đối.
 *
 * is_partial_lot = 1 : LÔ LẺ. Thiếu bất kỳ điều kiện nào ở trên - phòng gửi đã đụng vào
 *                      lô rồi (cân chia, cân đối hoặc dùng bớt) nên số lượng là con số đã
 *                      chốt: KHÔNG được xuất vượt lượng nhận và KHÔNG cân đối được.
 *                      Thiếu / thừa so với thực tế là chuyện của phòng gửi, phải xử lý
 *                      bằng cách phòng nhận từ chối nhận rồi chuyển lại.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->boolean('is_partial_lot')->default(false)->after('source_export_id');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->dropColumn('is_partial_lot');
        });
    }
};
