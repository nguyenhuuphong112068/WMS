<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU - LOẠI LƯU TRỮ CỦA VỊ TRÍ
 *
 * item_type : 'material' (Vật tư) | 'chemical' (Hoá chất) | 'standard' (Chất chuẩn)
 *
 * Chỉ đặt ở cấp SÂU NHẤT (vị trí) vì đây mới là chỗ thực sự đựng hàng: một phòng
 * hoặc một kệ có thể vừa để vật tư vừa để hoá chất, nhưng một ô thì chỉ dành cho
 * một loại. Có cột này để màn hình Tồn Kho của từng loại chỉ vẽ đúng các ô của mình
 * và ô chọn vị trí lúc nhập kho không đưa nhầm ô của loại khác.
 *
 * Cho phép null = "Dùng chung": ô chưa phân loại vẫn hiện ở cả ba màn hình, nên dữ
 * liệu định khu đang có không bị biến mất sau khi chạy migration này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('item_type', 20)->nullable()->after('shelf_id');

            $table->index('item_type', 'locations_item_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_item_type_index');
            $table->dropColumn('item_type');
        });
    }
};
