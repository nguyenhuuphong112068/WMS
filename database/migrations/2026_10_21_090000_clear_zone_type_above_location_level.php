<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phân loại định khu (Dự Phòng, Sử Dụng, Biệt Trữ...) chỉ khai ở từng VỊ TRÍ.
 * Xoá giá trị đã lỡ khai ở cấp kho/phòng, kệ/tủ, cột, tầng - nghiệp vụ chỉ đọc locations.zone_type.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['warehouses', 'shelves', 'columns', 'tiers'] as $table) {
            DB::table($table)->whereNotNull('zone_type')->update(['zone_type' => null]);
        }
    }

    public function down(): void
    {
        // Không khôi phục được giá trị đã xoá
    }
};
