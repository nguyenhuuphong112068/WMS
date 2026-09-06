<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TỒN - CẬP NHẬT HẠN DÙNG RETEST GHI THÊM KẾT QUẢ KIỂM NGHIỆM LẠI
 *
 * Ống chuẩn loại Retest sau mỗi lần kiểm nghiệm lại thường có Hàm lượng / Độ ẩm mới và
 * một Số phiếu kiểm nghiệm (CoA) mới. Khi tiếp tục Retest ở màn hình Tồn Kho Chất Chuẩn,
 * người dùng được sửa luôn ba giá trị này trên standard_imports; mỗi lần sửa chụp lại
 * giá trị TRƯỚC và SAU ở đây để giữ vết cùng với việc đổi hạn dùng.
 *
 * Chỉ dùng cho hướng xử lý "retest"; hướng "check online" và "chốt hạn xác định" bỏ trống.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_expiry_updates', function (Blueprint $table) {
            $table->string('old_potency', 100)->nullable()->after('retest_interval_months');
            $table->string('new_potency', 100)->nullable()->after('old_potency');
            $table->string('old_moisture', 100)->nullable()->after('new_potency');
            $table->string('new_moisture', 100)->nullable()->after('old_moisture');
            $table->string('old_coa_no', 100)->nullable()->after('new_moisture');
            $table->string('new_coa_no', 100)->nullable()->after('old_coa_no');
        });
    }

    public function down(): void
    {
        Schema::table('standard_expiry_updates', function (Blueprint $table) {
            $table->dropColumn([
                'old_potency',
                'new_potency',
                'old_moisture',
                'new_moisture',
                'old_coa_no',
                'new_coa_no',
            ]);
        });
    }
};
