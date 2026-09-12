<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU - PHÂN LOẠI & MÀU
 *
 * Thêm hai thuộc tính cho cả 5 cấp định khu (Kho/Phòng, Kệ/Tủ, Cột, Tầng, Vị Trí):
 *
 *   zone_type : phân loại khu vực theo trạng thái hàng đang để trong đó
 *               reserve   - Dự Phòng
 *               in_use    - Sử Dụng
 *               quarantine- Biệt Trữ
 *               pending   - Chờ Quyết Định
 *               rejected  - Loại Bỏ/Chờ Huỷ
 *               (null = chưa phân loại, dữ liệu cũ giữ nguyên không bị mất)
 *
 *   color     : màu người dùng tự chọn cho mục định khu đó, dạng '#RRGGBB'.
 *               Để trống thì màn hình tự lấy màu mặc định của phân loại
 *               (App\Support\ZoneType), chưa phân loại nữa thì dùng màu chủ đạo.
 *
 * Đặt ở cả 5 cấp vì người dùng cần tô màu được cả nhóm định khu (kho, kệ, cột, tầng)
 * lẫn từng ô vị trí riêng lẻ.
 */
return new class extends Migration
{
    /** Năm bảng của cây định khu. */
    private const TABLES = ['warehouses', 'shelves', 'columns', 'tiers', 'locations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'zone_type')) {
                    $blueprint->string('zone_type', 30)->nullable()->after('code');
                    $blueprint->index('zone_type', $table.'_zone_type_index');
                }

                if (! Schema::hasColumn($table, 'color')) {
                    $blueprint->string('color', 7)->nullable()->after('zone_type');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'zone_type')) {
                    $blueprint->dropIndex($table.'_zone_type_index');
                    $blueprint->dropColumn('zone_type');
                }

                if (Schema::hasColumn($table, 'color')) {
                    $blueprint->dropColumn('color');
                }
            });
        }
    }
};
