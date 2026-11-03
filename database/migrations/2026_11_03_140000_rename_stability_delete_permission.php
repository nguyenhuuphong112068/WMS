<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Đánh giá hạn dùng CTC không còn xoá: mốc chưa có kết quả nay "Huỷ mốc" (StandardStabilityController::cancelItem,
 * trạng thái mốc "Huỷ"), phiếu vẫn "Huỷ / Mở lại". Đổi tên hiển thị quyền stability_standard_delete cho đúng
 * (giữ tên quyền để không đổi phân quyền đang có).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->where('name', 'stability_standard_delete')->update([
            'display_name' => 'Huỷ Phiếu / Mốc Đánh Giá Chuẩn Thứ Cấp',
            'description' => 'Huỷ / mở lại phiếu đánh giá hạn dùng chuẩn thứ cấp, huỷ mốc đánh giá chưa có kết quả',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'stability_standard_delete')->update([
            'display_name' => 'Xoá Hồ Sơ Đánh Giá Chuẩn Thứ Cấp',
            'description' => 'Xoá dòng đánh giá trong hồ sơ chuẩn thứ cấp',
            'updated_at' => now(),
        ]);
    }
};
