<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BƯỚC "CHỜ KIỂM TRA" CHO NHẬP VẬT TƯ / HOÁ CHẤT / CHẤT CHUẨN.
 *
 * Thêm is_checked, checked_by, checked_at cho cả 3 bảng nhập (material_imports,
 * chemical_imports, standard_imports) và 3 bảng lịch sử của chúng (để snapshot lịch
 * sử luôn đủ cột như bảng chính).
 *
 *   is_checked : 0 = Chờ kiểm tra (vừa nhập, định khu ở vị trí zone_type=quarantine,
 *                    CHƯA được cộng vào tồn kho, CHƯA được đề nghị / sử dụng)
 *                1 = Đã kiểm tra (đã xác nhận qua bước "Xác nhận kiểm tra", được
 *                    định khu lại vị trí thật, từ đây mới cộng vào tồn kho)
 *   checked_by : người xác nhận kiểm tra (actor() lúc bấm Xác nhận kiểm tra)
 *   checked_at : thời điểm xác nhận kiểm tra
 *
 * Dữ liệu cũ (nhập trước khi có tính năng này) mặc định is_checked = 1 để không bị
 * rớt khỏi tồn kho hiện tại.
 */
return new class extends Migration
{
    /** Bảng nhập chính -> bảng lịch sử tương ứng. */
    private const TABLES = [
        'material_imports' => 'material_import_histories',
        'chemical_imports' => 'chemical_import_histories',
        'standard_imports' => 'standard_import_histories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (! Schema::hasColumn($table, 'is_checked')) {
                        $blueprint->tinyInteger('is_checked')->default(1)->after('status_id');
                        $blueprint->index('is_checked', $table.'_is_checked_index');
                    }
                    if (! Schema::hasColumn($table, 'checked_by')) {
                        $blueprint->string('checked_by')->nullable()->after('is_checked');
                    }
                    if (! Schema::hasColumn($table, 'checked_at')) {
                        $blueprint->timestamp('checked_at')->nullable()->after('checked_by');
                    }
                });

                // Dữ liệu có trước tính năng này: coi như đã kiểm tra, không rớt khỏi tồn.
                DB::table($table)->update(['is_checked' => 1]);
            }

            if (Schema::hasTable($historyTable)) {
                Schema::table($historyTable, function (Blueprint $blueprint) use ($historyTable) {
                    if (! Schema::hasColumn($historyTable, 'is_checked')) {
                        $blueprint->tinyInteger('is_checked')->nullable()->after('status_id');
                    }
                    if (! Schema::hasColumn($historyTable, 'checked_by')) {
                        $blueprint->string('checked_by')->nullable()->after('is_checked');
                    }
                    if (! Schema::hasColumn($historyTable, 'checked_at')) {
                        $blueprint->timestamp('checked_at')->nullable()->after('checked_by');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    if (Schema::hasColumn($table, 'is_checked')) {
                        $blueprint->dropIndex($table.'_is_checked_index');
                        $blueprint->dropColumn('is_checked');
                    }
                    if (Schema::hasColumn($table, 'checked_by')) {
                        $blueprint->dropColumn('checked_by');
                    }
                    if (Schema::hasColumn($table, 'checked_at')) {
                        $blueprint->dropColumn('checked_at');
                    }
                });
            }

            if (Schema::hasTable($historyTable)) {
                Schema::table($historyTable, function (Blueprint $blueprint) use ($historyTable) {
                    foreach (['is_checked', 'checked_by', 'checked_at'] as $col) {
                        if (Schema::hasColumn($historyTable, $col)) {
                            $blueprint->dropColumn($col);
                        }
                    }
                });
            }
        }
    }
};
