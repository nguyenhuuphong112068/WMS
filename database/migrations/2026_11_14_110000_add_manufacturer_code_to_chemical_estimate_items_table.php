<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Code nhà sản xuất" của từng mặt hàng Dự Trù Hoá Chất.
 *
 * Người lập phiếu chọn từ các Code NSX đã khai báo Cổng thông tin quốc gia của mã danh mục
 * (chemical_portal_declarations) hoặc gõ code mới; code chưa khai báo chỉ CẢNH BÁO, không chặn.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chemical_estimate_items') && ! Schema::hasColumn('chemical_estimate_items', 'manufacturer_code')) {
            Schema::table('chemical_estimate_items', function (Blueprint $table) {
                $table->string('manufacturer_code', 100)->nullable()->after('chem_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('chemical_estimate_items') && Schema::hasColumn('chemical_estimate_items', 'manufacturer_code')) {
            Schema::table('chemical_estimate_items', function (Blueprint $table) {
                $table->dropColumn('manufacturer_code');
            });
        }
    }
};
