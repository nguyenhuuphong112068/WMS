<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MODULE VPP (văn phòng phẩm) - bộ bảng vpp_* tách khỏi Vật Tư.
 *
 * VPP có đủ chức năng như Vật Tư (Danh Mục công ty + phòng, Nhập, Sử Dụng, Tồn Kho, Dự Trù,
 * Thống Kê, Trình Ký) nhưng dữ liệu nằm ở bộ bảng riêng: mỗi bảng material_* (trừ material_names -
 * Dữ liệu gốc Tên Vật Tư vẫn dùng chung) được nhân bản cấu trúc sang vpp_* cùng tên đuôi. Cột
 * khoá ngoại mang tên bảng (material_import_id, material_estimate_id...) đổi theo thành vpp_*;
 * material_names_id / material_name giữ nguyên vì trỏ về dữ liệu gốc dùng chung.
 *
 * Bảng tạo bằng CREATE TABLE ... LIKE nên giữ đủ kiểu cột, giá trị mặc định và chỉ mục của bảng
 * Vật Tư ở thời điểm chạy migration.
 */
return new class extends Migration
{
    /** Đuôi tên bảng (sau "material_") nhân bản sang vpp_ */
    public const TABLES = [
        'categories', 'category_histories', 'department_categories',
        'imports', 'import_histories', 'import_attachments', 'balancings',
        'exports', 'export_histories', 'quarantines', 'calibrations', 'calibration_units',
        'stocktakes', 'stocktake_items', 'pick_waves', 'pick_lines',
        'request_lists', 'request_items', 'request_signs', 'request_sign_candidates',
        'request_sign_rules', 'request_sign_rule_users',
        'transfer_requests', 'transfer_items', 'transfer_signs', 'transfer_sign_steps',
        'estimates', 'estimate_items', 'estimate_item_amounts', 'estimate_signs',
        'estimate_histories', 'estimate_attachments',
        'periodic_request_list', 'periodic_request_item', 'periodic_request_list_histories',
        'watchlist_items',
    ];

    /** Cột trỏ dữ liệu gốc dùng chung - không đổi tên */
    private const KEEP_COLUMNS = ['material_names_id', 'material_name'];

    public function up(): void
    {
        foreach (self::TABLES as $suffix) {
            $from = 'material_'.$suffix;
            $to = 'vpp_'.$suffix;

            if (! Schema::hasTable($from) || Schema::hasTable($to)) {
                continue;
            }

            DB::statement("CREATE TABLE `{$to}` LIKE `{$from}`");

            foreach (Schema::getColumnListing($to) as $column) {
                if (str_starts_with($column, 'material_') && ! in_array($column, self::KEEP_COLUMNS, true)) {
                    Schema::table($to, fn ($table) => $table->renameColumn($column, 'vpp_'.substr($column, strlen('material_'))));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $suffix) {
            Schema::dropIfExists('vpp_'.$suffix);
        }
    }
};
