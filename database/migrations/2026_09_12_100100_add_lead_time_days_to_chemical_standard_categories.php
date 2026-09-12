<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THỜI GIAN ĐẶT HÀNG CHO DANH MỤC HOÁ CHẤT & CHẤT CHUẨN
 *
 * lead_time_days = số ngày từ lúc đặt hàng tới lúc hàng về công ty. Dùng để biết phải
 * làm dự trù trước bao lâu thì hàng mới kịp về.
 *
 * Vật tư đã có cột này ở migration 2026_09_12_100000; đây là bản song song cho hai
 * danh mục còn lại, cùng nằm ở mức công ty vì thời gian chờ hàng là chuyện của nhà cung
 * cấp chứ không phải cách dùng riêng của từng phòng.
 */
return new class extends Migration
{
    /** Bảng danh mục => bảng lịch sử tương ứng. */
    private const TABLES = [
        'chemical_categories' => 'chemical_category_histories',
        'standard_categories' => 'standard_category_histories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            foreach ([$table, $historyTable] as $name) {
                Schema::table($name, function (Blueprint $blueprint) {
                    $blueprint->unsignedSmallInteger('lead_time_days')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            foreach ([$table, $historyTable] as $name) {
                Schema::table($name, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('lead_time_days');
                });
            }
        }
    }
};
