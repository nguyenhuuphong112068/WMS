<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CHUYỂN ĐƠN VỊ TÍNH TỪ DANH MỤC CHẤT CHUẨN CÔNG TY XUỐNG DANH MỤC CỦA PHÒNG
 *
 * Song song với migration cùng ngày bên hoá chất. Đơn vị tính là CÁCH DÙNG của từng
 * phòng chứ không phải BẢN CHẤT của chất chuẩn, nên nó nằm ở department_standards.
 *
 * Không có fallback "để trống = theo danh mục" cho đơn vị: phòng khai chất chuẩn nào
 * thì khai luôn đơn vị của phòng cho chất chuẩn đó.
 *
 * Cột unit_id trên standard_category_histories được GIỮ LẠI để không mất ảnh chụp của
 * những lần thay đổi đã xảy ra, nhưng từ nay không ghi thêm giá trị nào vào đó nữa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_standards', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('category_id'); // -> units.id
        });

        // Chép đơn vị đang có của danh mục xuống từng phòng, để sau migration mọi màn
        // hình vẫn hiện đúng đơn vị như trước.
        DB::table('department_standards')
            ->join('standard_categories', 'department_standards.category_id', '=', 'standard_categories.id')
            ->update(['department_standards.unit_id' => DB::raw('standard_categories.unit_id')]);

        Schema::table('standard_categories', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('standard_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('manufacturers_id');
        });

        // Trả đơn vị về danh mục chung: lấy đơn vị của phòng đã khai sớm nhất.
        DB::table('standard_categories')->update([
            'unit_id' => DB::raw(
                '(select min(unit_id) from department_standards'
                .' where department_standards.category_id = standard_categories.id)'
            ),
        ]);

        Schema::table('department_standards', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });
    }
};
