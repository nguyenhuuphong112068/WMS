<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Kiểm soát khối lượng bằng cân:
|
| - gross_weight_before: khối lượng "Bì + Chuẩn" cân TRƯỚC lần sử dụng đầu tiên.
|   Chỉ nhập một lần duy nhất, ngay trên phiếu sử dụng đầu tiên của ống chuẩn.
| - tare_weight_after: khối lượng "Bì" cân SAU khi sử dụng, nhập ở màn hình
|   Tồn Kho Chất Chuẩn, tab Kiểm soát Khối lượng. Chỉ nhập được khi đã có
|   gross_weight_before, vì hai số này luôn đi thành một cặp để đối chiếu.
|
| Đơn vị của hai cột này là đơn vị tính của ống chuẩn (mg / g / ml...).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->decimal('gross_weight_before', 15, 4)->nullable()->after('weight_deviation_remark');
            $table->decimal('tare_weight_after', 15, 4)->nullable()->after('gross_weight_before');
        });
    }

    public function down(): void
    {
        Schema::table('standard_imports', function (Blueprint $table) {
            $table->dropColumn(['gross_weight_before', 'tare_weight_after']);
        });
    }
};
