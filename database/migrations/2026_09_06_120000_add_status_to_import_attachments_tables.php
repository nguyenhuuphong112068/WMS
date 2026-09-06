<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trạng thái sử dụng của từng file đính kèm phiếu nhập (Hoá chất / Vật tư / Chất chuẩn).
 *
 *      is_active = 1 -> Đang sử dụng   (mặc định)
 *      is_active = 0 -> Ngưng sử dụng
 *
 * Người dùng có quyền tương ứng đổi trạng thái này ở cửa sổ xem file đính kèm trên
 * màn hình Nhập và màn hình Tồn Kho. File ngưng sử dụng vẫn mở xem được, chỉ hiển thị
 * kèm nhãn trạng thái để biết không còn hiệu lực.
 */
return new class extends Migration
{
    private const TABLES = [
        'chemical_import_attachments',
        'material_import_attachments',
        'standard_import_attachments',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'is_active')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_active')->default(1)->after('file_type');
                $table->string('status_changed_by')->nullable()->after('is_active');
                $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'is_active')) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['is_active', 'status_changed_by', 'status_changed_at']);
            });
        }
    }
};
