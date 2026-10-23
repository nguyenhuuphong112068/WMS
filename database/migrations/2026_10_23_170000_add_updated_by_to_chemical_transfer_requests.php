<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng chemical_transfer_requests dựng lại ở 2026_09_05_140000 nhưng quên cột updated_by, trong khi
 * ChemicalExportController ghi updated_by khi sửa / gửi / cấp phát / nhận / từ chối đề nghị liên phòng
 * ban và tab "Đề nghị chuyển liên phòng ban" hiển thị "Người cập nhật" từ cột này.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chemical_transfer_requests', 'updated_by')) {
            Schema::table('chemical_transfer_requests', function (Blueprint $table) {
                $table->string('updated_by')->nullable()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chemical_transfer_requests', 'updated_by')) {
            Schema::table('chemical_transfer_requests', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
    }
};
