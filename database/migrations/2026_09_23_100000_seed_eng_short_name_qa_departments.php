<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Đánh dấu phòng QA (Đảm Bảo Chất Lượng) bằng EngshortName = 'QA', cùng cách các phòng
 * Kiểm Tra Chất Lượng đang mang QC / QC1 / QC2.
 *
 * Vật tư phân loại "Cần QA hiệu chuẩn trước khi sử dụng" khi được cấp phát sẽ tự báo cho
 * người dùng thuộc các phòng có EngshortName = 'QA' (xem MaterialExportController).
 * Chỉ ghi vào phòng chưa khai EngshortName để không đè giá trị người dùng đã sửa tay.
 */
return new class extends Migration
{
    private array $map = [
        'ĐBCL-NM1' => 'QA',
        'ĐBCL-NM2' => 'QA',
    ];

    public function up(): void
    {
        foreach ($this->map as $shortName => $engShort) {
            DB::table('deparments')
                ->where('shortName', $shortName)
                ->whereNull('EngshortName')
                ->update(['EngshortName' => $engShort]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $shortName => $engShort) {
            DB::table('deparments')
                ->where('shortName', $shortName)
                ->where('EngshortName', $engShort)
                ->update(['EngshortName' => null]);
        }
    }
};
