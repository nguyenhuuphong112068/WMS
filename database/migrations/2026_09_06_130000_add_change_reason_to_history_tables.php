<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LÝ DO ĐIỀU CHỈNH DỮ LIỆU GỐC / DANH MỤC
 *
 * Mọi thao tác Sửa / Khoá / Mở khoá một bản ghi dữ liệu gốc hoặc danh mục bắt buộc phải
 * nhập lý do. Lý do được lưu cùng dòng lịch sử thay đổi để giải trình về sau, tách khỏi
 * change_note (mô tả trường nào đổi từ gì sang gì do hệ thống tự sinh).
 *
 * Bốn bảng lịch sử đang dùng: datamaster_histories (chung nhóm Dữ Liệu Gốc) và ba bảng
 * lịch sử của nhóm Danh Mục.
 */
return new class extends Migration
{
    private const TABLES = [
        'datamaster_histories',
        'material_category_histories',
        'chemical_category_histories',
        'standard_category_histories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'change_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    // Nêu rõ charset: bảng datamaster_histories mặc định latin1, để mặc định
                    // thì tiếng Việt có dấu trong lý do sẽ hỏng khi lưu.
                    $t->text('change_reason')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->after('change_note');
                });
            } else {
                DB::statement("ALTER TABLE `{$table}` MODIFY `change_reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'change_reason')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('change_reason');
                });
            }
        }
    }
};
