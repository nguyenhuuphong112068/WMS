<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mặt bằng kho/phòng (Định Khu > Cấu trúc kho > Mặt bằng): kích thước phòng + cửa ra vào ở
 * warehouses, vị trí đặt từng kệ/tủ trong phòng ở shelves. Đơn vị mét.
 *
 *   warehouses.layout_width / layout_depth : chiều ngang / chiều sâu của phòng
 *   warehouses.layout_door_wall            : tường có cửa (top | right | bottom | left), null = chưa khai
 *   warehouses.layout_door_offset          : khoảng cách từ góc tường tới mép cửa
 *   shelves.layout_x / layout_y            : TÂM kệ trên mặt bằng, gốc là góc trên-trái sơ đồ; null = chưa đặt
 *   shelves.layout_width / layout_depth    : bề ngang (mặt trước) và bề sâu của kệ
 *   shelves.layout_rotation                : hướng mặt trước 0 = xuống, 90 = trái, 180 = lên, 270 = phải
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->decimal('layout_width', 6, 2)->nullable()->after('name');
            $table->decimal('layout_depth', 6, 2)->nullable()->after('layout_width');
            $table->string('layout_door_wall', 10)->nullable()->after('layout_depth');
            $table->decimal('layout_door_offset', 6, 2)->nullable()->after('layout_door_wall');
        });

        Schema::table('shelves', function (Blueprint $table) {
            $table->decimal('layout_x', 6, 2)->nullable()->after('tier_order');
            $table->decimal('layout_y', 6, 2)->nullable()->after('layout_x');
            $table->decimal('layout_width', 5, 2)->nullable()->after('layout_y');
            $table->decimal('layout_depth', 5, 2)->nullable()->after('layout_width');
            $table->unsignedSmallInteger('layout_rotation')->nullable()->after('layout_depth');
        });
    }

    public function down(): void
    {
        Schema::table('shelves', function (Blueprint $table) {
            $table->dropColumn(['layout_x', 'layout_y', 'layout_width', 'layout_depth', 'layout_rotation']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['layout_width', 'layout_depth', 'layout_door_wall', 'layout_door_offset']);
        });
    }
};
