<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mã danh mục công ty (vật tư / hoá chất / chất chuẩn) mới tạo CHỜ KHAI THỜI GIAN ĐẶT HÀNG.
 *
 * lead_time_pending = 1 khi thêm mã mới: trang chủ (HomeController::leadTimeReviews) báo cho người
 * có quyền category_*_lead_time vào cập nhật; về 0 khi người đó lưu ở nút điều chỉnh thời gian
 * đặt hàng (UpdatesCategoryLeadTime). Dữ liệu cũ để 0 - không báo dồn hàng nghìn mã đã có.
 */
return new class extends Migration
{
    private const TABLES = ['material_categories', 'chemical_categories', 'standard_categories'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'lead_time_pending')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('lead_time_pending')->default(0)->after('lead_time_days');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'lead_time_pending')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('lead_time_pending');
                });
            }
        }
    }
};
