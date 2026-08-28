<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NHẬP - VỊ TRÍ LƯU TRỮ CỦA MÃ XUẤT NHẬP
 *
 * location_id : -> locations.id, tức cấp SÂU NHẤT của định khu (vị trí).
 *
 * Chỉ lưu một cột này chứ không lưu kèm warehouse_id / room_id / shelf_id:
 * bảng locations đã mang sẵn id của cả ba cấp trên, join một lần là ra đủ
 * Kho -> Phòng -> Kệ -> Vị Trí. Lưu lặp bốn cột sẽ có ngày lệch nhau khi
 * định khu được sắp xếp lại.
 *
 * Cho phép null: mã nhập vào nhưng chưa xếp vị trí vẫn ghi nhận được, màn hình
 * Tồn Kho hiện là "Chưa xếp vị trí" để biết mà bổ sung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('supplier_id');

            $table->index('location_id', 'chemical_imports_location_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            $table->dropIndex('chemical_imports_location_id_index');
            $table->dropColumn('location_id');
        });
    }
};
