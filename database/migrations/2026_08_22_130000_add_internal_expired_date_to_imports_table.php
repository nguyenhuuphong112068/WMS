<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - HẠN DÙNG NỘI BỘ CỦA PHIẾU NHẬP
 *
 * internal_expired_date : hạn dùng nội bộ, mặc định NULL vì phải được xác định
 *                         bằng tay ở màn hình Tồn Kho Hoá Chất, không sinh lúc nhập kho.
 *
 * Cách tính (xem ChemicalInventoryController::internalExpiry):
 *      hạn dùng nội bộ = ngày xác định + chemical_categories.shelf_life_months (tháng)
 *      nếu kết quả vượt quá imports.expired_date thì lấy imports.expired_date
 *
 * Chỉ hoá chất có chemical_categories.shelf_life_months > 0 mới xác định được.
 * Ngày xác định và người xác định không lưu ở cột riêng, được ghi trong Audit Trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->date('internal_expired_date')->nullable()->after('expired_date');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->dropColumn('internal_expired_date');
        });
    }
};
