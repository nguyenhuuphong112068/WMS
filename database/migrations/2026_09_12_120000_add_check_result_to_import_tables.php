<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TÌNH TRẠNG KIỂM TRA 3 MỨC CHO CẢ 3 BẢNG NHẬP.
 *
 *   check_result = 'pending' : Chờ kiểm tra  (vừa nhập, đang nằm khu Biệt Trữ)
 *                  'passed'  : Kiểm tra Đạt  (được nhập kho, cộng tồn, cho đề nghị/sử dụng)
 *                  'failed'  : Không đạt     (TRẢ HÀNG - không bao giờ nhập kho)
 *
 *   check_note : kết luận / lý do kiểm tra, BẮT BUỘC khi kết quả là Không đạt.
 *
 * QUAN HỆ VỚI is_checked (cờ chặn tồn kho đang dùng ở ~15 chỗ tính tồn):
 *
 *      is_checked = 1  <=>  check_result = 'passed'
 *      is_checked = 0  <=>  check_result = 'pending' HOẶC 'failed'
 *
 * Nhờ vậy lô Không đạt tự động nằm ngoài tồn kho, ngoài danh sách chọn lô để xuất và
 * ngoài tab "Chờ kiểm tra" - đúng nghĩa trả hàng, không bao giờ nhập kho. Hai cột luôn
 * được ghi cùng lúc ở confirmCheck() của 3 controller Nhập.
 */
return new class extends Migration
{
    private const TABLES = [
        'material_imports' => 'material_import_histories',
        'chemical_imports' => 'chemical_import_histories',
        'standard_imports' => 'standard_import_histories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            foreach ([$table, $historyTable] as $target) {
                if (! Schema::hasTable($target)) {
                    continue;
                }

                Schema::table($target, function (Blueprint $blueprint) use ($target) {
                    if (! Schema::hasColumn($target, 'check_result')) {
                        $blueprint->string('check_result', 20)->default('pending')->after('is_checked');
                        $blueprint->index('check_result', $target.'_check_result_index');
                    }
                    if (! Schema::hasColumn($target, 'check_note')) {
                        $blueprint->string('check_note', 500)->nullable()->after('checked_at');
                    }
                });
            }

            // Dữ liệu sẵn có: đã kiểm tra -> Đạt, còn lại -> Chờ kiểm tra
            DB::table($table)->where('is_checked', 1)->update(['check_result' => 'passed']);
            DB::table($table)->where('is_checked', 0)->update(['check_result' => 'pending']);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $historyTable) {
            foreach ([$table, $historyTable] as $target) {
                if (! Schema::hasTable($target)) {
                    continue;
                }

                Schema::table($target, function (Blueprint $blueprint) use ($target) {
                    if (Schema::hasColumn($target, 'check_result')) {
                        $blueprint->dropIndex($target.'_check_result_index');
                        $blueprint->dropColumn('check_result');
                    }
                    if (Schema::hasColumn($target, 'check_note')) {
                        $blueprint->dropColumn('check_note');
                    }
                });
            }
        }
    }
};
