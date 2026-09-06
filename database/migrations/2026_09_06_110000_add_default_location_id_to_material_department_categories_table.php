<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỊNH KHU CỦA VẬT TƯ THEO TỪNG PHÒNG BAN
 *
 * Hoá chất (chemical_department_categories) và chất chuẩn (standard_department_categories)
 * đã có cột này từ lâu, riêng vật tư thì chưa. Thiếu nó, phòng không khai được "vật tư này
 * để ở chỗ nào" nên mỗi lần nhập lại phải chọn tay vị trí từ đầu.
 *
 * default_location_id : ĐỊNH KHU - chỗ dự kiến để vật tư, chỉ dùng để điền sẵn khi nhập.
 *                       Vị trí THỰC TẾ của từng lô nằm ở material_imports.location_id,
 *                       hai cái này khác nhau và không thay thế cho nhau được.
 */
return new class extends Migration
{
    private const TABLE = 'material_department_categories';

    public function up(): void
    {
        if (Schema::hasColumn(self::TABLE, 'default_location_id')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('default_location_id')->nullable()->after('unit_id'); // -> locations.id
            $table->index('default_location_id', 'material_department_categories_default_location_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'default_location_id')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->dropIndex('material_department_categories_default_location_id_index');
            $table->dropColumn('default_location_id');
        });
    }
};
