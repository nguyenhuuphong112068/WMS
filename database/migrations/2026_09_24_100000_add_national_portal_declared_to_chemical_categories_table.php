<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Đã khai báo trên Cổng thông tin quốc gia" của Danh Mục Hoá Chất Công Ty.
 *
 * Chỉ xác nhận được MỘT lần, một chiều Chưa -> Đã (ChemicalCategoryController::declarePortal),
 * ghi lại người + thời điểm xác nhận, sau đó không sửa / gỡ được nữa.
 * Bảng lịch sử giữ thêm cờ này trong ảnh chụp mỗi lần thay đổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chemical_categories') && ! Schema::hasColumn('chemical_categories', 'national_portal_declared')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->tinyInteger('national_portal_declared')->default(0)->after('lead_time_days');
                $table->string('national_portal_declared_by')->nullable()->after('national_portal_declared');
                $table->dateTime('national_portal_declared_at')->nullable()->after('national_portal_declared_by');
            });
        }

        if (Schema::hasTable('chemical_category_histories') && ! Schema::hasColumn('chemical_category_histories', 'national_portal_declared')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                // Ảnh chụp trước khi có cột này để NULL -> hiển thị "—"
                $table->tinyInteger('national_portal_declared')->nullable()->after('lead_time_days');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chemical_category_histories') && Schema::hasColumn('chemical_category_histories', 'national_portal_declared')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                $table->dropColumn('national_portal_declared');
            });
        }

        if (Schema::hasTable('chemical_categories') && Schema::hasColumn('chemical_categories', 'national_portal_declared')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->dropColumn(['national_portal_declared', 'national_portal_declared_by', 'national_portal_declared_at']);
            });
        }
    }
};
