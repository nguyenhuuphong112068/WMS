<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NGƯỠNG TỒN TỐI ĐA CỦA TỪNG PHÒNG BAN
 *
 * Đứng cạnh min_stock đã có sẵn ở cả ba bảng danh mục theo phòng ban. Hai ngưỡng dùng cho
 * hai đầu ngược nhau:
 *
 *   - min_stock : tồn xuống dưới mức này thì coi là sắp hết, cần dự trù thêm.
 *   - max_stock : tồn vượt mức này thì phòng đang trữ quá nhiều - kho chật, hàng dễ hết
 *                 hạn trước khi dùng tới. Hệ thống CẢNH BÁO lúc nhập và lúc dự trù, không
 *                 chặn: quyết định vẫn là của người dùng.
 *
 * Theo đúng đơn vị tính của phòng (cột unit_id cùng bảng), giống min_stock.
 */
return new class extends Migration
{
    private const TABLES = [
        'material_department_categories',
        'chemical_department_categories',
        'standard_department_categories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('max_stock', 15, 4)->nullable()->after('min_stock');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('max_stock');
            });
        }
    }
};
