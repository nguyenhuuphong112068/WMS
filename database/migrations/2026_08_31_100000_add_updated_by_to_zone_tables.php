<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU - CHUẨN HOÁ CỘT NGƯỜI THAO TÁC CHO 4 CẤP
 *
 * Bốn bảng định khu tạo từ đầu dự án thiếu updated_by, còn created_by lại khai là
 * bigint trong khi ZoneController (và cột "Người Tạo" trên màn hình) ghi/đọc HỌ TÊN.
 * Hậu quả: mỗi lần Sửa / Khoá / Mở khoá đều chết với lỗi
 * "Unknown column 'updated_by' in 'field list'", còn Người Tạo thì luôn trống.
 *
 * Đưa cả hai cột về đúng bộ cột chuẩn của bảng nghiệp vụ:
 * id, status_id, created_by, created_at, updated_by, updated_at.
 * Dữ liệu created_by hiện tại đang trống toàn bộ nên đổi kiểu không mất gì.
 */
return new class extends Migration
{
    private const TABLES = ['warehouses', 'rooms', 'shelves', 'locations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('created_by')->nullable()->change();
            });

            if (Schema::hasColumn($table, 'updated_by')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('updated_by')->nullable()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'updated_by')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('updated_by');
                });
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }
};
