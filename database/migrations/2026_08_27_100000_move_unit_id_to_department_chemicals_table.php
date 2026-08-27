<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CHUYỂN ĐƠN VỊ TÍNH TỪ DANH MỤC HOÁ CHẤT CÔNG TY XUỐNG DANH MỤC CỦA PHÒNG
 *
 * Trước đây đơn vị tính nằm ở chemical_categories: cả công ty dùng chung một đơn vị gốc
 * cho mỗi hoá chất. Thực tế mỗi phòng lại nhập / xuất theo đơn vị riêng của phòng mình
 * (phòng đong theo ml, phòng cân theo g), nên đơn vị là CÁCH DÙNG chứ không phải BẢN
 * CHẤT của chất - đúng chỗ của nó là department_chemicals.
 *
 * Vì vậy ở đây không còn fallback "để trống = theo danh mục" cho đơn vị: phòng khai
 * hoá chất nào thì phải khai luôn đơn vị của phòng cho hoá chất đó.
 *
 * Cột unit_id trên chemical_category_histories được GIỮ LẠI: đó là ảnh chụp lịch sử của
 * những lần thay đổi đã xảy ra, xoá đi là mất vết. Từ nay không ghi thêm giá trị nào vào
 * cột này nữa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_chemicals', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('category_id'); // -> units.id
        });

        // Chép đơn vị đang có của danh mục xuống từng phòng, để sau migration mọi màn
        // hình vẫn hiện đúng đơn vị như trước.
        DB::table('department_chemicals')
            ->join('chemical_categories', 'department_chemicals.category_id', '=', 'chemical_categories.id')
            ->update(['department_chemicals.unit_id' => DB::raw('chemical_categories.unit_id')]);

        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('manufacturers_id');
        });

        // Trả đơn vị về danh mục chung: lấy đơn vị của phòng đã khai sớm nhất.
        DB::table('chemical_categories')->update([
            'unit_id' => DB::raw(
                '(select min(unit_id) from department_chemicals'
                .' where department_chemicals.category_id = chemical_categories.id)'
            ),
        ]);

        Schema::table('department_chemicals', function (Blueprint $table) {
            $table->dropColumn('unit_id');
        });
    }
};
