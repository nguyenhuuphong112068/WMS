<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Mã hồ sơ khai báo" trên Cổng thông tin quốc gia của Danh Mục Hoá Chất Công Ty.
 *
 * Bắt buộc nhập khi xác nhận "Đã khai báo" (ChemicalCategoryController::declarePortal),
 * ghi cùng lúc với cờ national_portal_declared và khoá luôn sau đó.
 * Các mã đã xác nhận trước khi có cột này để NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chemical_categories') && ! Schema::hasColumn('chemical_categories', 'national_portal_declaration_no')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->string('national_portal_declaration_no', 100)->nullable()->after('national_portal_declared');
            });
        }

        if (Schema::hasTable('chemical_category_histories') && ! Schema::hasColumn('chemical_category_histories', 'national_portal_declaration_no')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                $table->string('national_portal_declaration_no', 100)->nullable()->after('national_portal_declared');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chemical_category_histories') && Schema::hasColumn('chemical_category_histories', 'national_portal_declaration_no')) {
            Schema::table('chemical_category_histories', function (Blueprint $table) {
                $table->dropColumn('national_portal_declaration_no');
            });
        }

        if (Schema::hasTable('chemical_categories') && Schema::hasColumn('chemical_categories', 'national_portal_declaration_no')) {
            Schema::table('chemical_categories', function (Blueprint $table) {
                $table->dropColumn('national_portal_declaration_no');
            });
        }
    }
};
