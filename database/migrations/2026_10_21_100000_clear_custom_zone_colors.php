<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mỗi phân loại định khu gắn cố định một màu (App\Support\ZoneType::DEFAULT_COLORS),
 * bỏ màu tự chọn: xoá màu riêng đã lưu ở cả 5 cấp định khu.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['warehouses', 'shelves', 'columns', 'tiers', 'locations'] as $table) {
            DB::table($table)->whereNotNull('color')->update(['color' => null]);
        }
    }

    public function down(): void
    {
        // Không khôi phục được màu đã xoá
    }
};
