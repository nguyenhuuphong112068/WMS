<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mặt bằng kho/phòng đa giác + cửa kéo thả.
 *
 * - layout_polygon: toạ độ các góc phòng theo mét, JSON gọn [[x,y],...] chạy theo chiều kim đồng hồ khi nhìn từ
 *   trên xuống (x sang phải, y xuống dưới), gốc (0,0) là góc trên-trái của khung bao. NULL = phòng hình chữ nhật
 *   layout_width × layout_depth như trước. layout_width / layout_depth luôn là kích thước khung bao của đa giác.
 * - layout_door: {"edge":<chỉ số cạnh>,"offset":<m từ góc đầu cạnh>,"width":<m>}. Cạnh i đi từ góc i tới góc i+1.
 *   NULL = dùng layout_door_wall / layout_door_offset cũ (chỉ cho phòng chữ nhật, cửa rộng 1 m).
 * Dùng cột text (không dùng json) để chuỗi lưu đúng như đã ghi, so sánh thay đổi cho lịch sử không bị lệch định dạng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->text('layout_polygon')->nullable()->after('layout_depth');
            $table->string('layout_door', 120)->nullable()->after('layout_door_offset');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['layout_polygon', 'layout_door']);
        });
    }
};
