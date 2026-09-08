<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KHỐI LƯỢNG QUY ĐỔI CHO ĐƠN VỊ ĐẾM / BAO BÌ
 *
 * Đơn vị tính là của riêng từng phòng (chemical_department_categories.unit_id). Khi phòng
 * khai đơn vị thuộc nhóm "đếm / bao bì" (chai, thùng, bao...), hệ thống không tự biết
 * 1 chai nặng bao nhiêu để quy ra kg khi đối chiếu "Ngưỡng khối lượng hoá chất tồn trữ
 * lớn nhất tại một thời điểm (kg)" - Phụ lục IV NĐ 24/2026/NĐ-CP.
 *
 * pack_weight_kg giữ đúng con số đó: khối lượng hoá chất (kg) chứa trong 1 đơn vị đếm mà
 * phòng đang dùng cho mã này. Chỉ có ý nghĩa khi unit_id thuộc nhóm count; đơn vị khối
 * lượng / thể tích vẫn quy đổi bằng App\Support\UnitConverter như cũ. Để trống = giữ
 * nguyên hành vi hiện tại (lô bị gom vào phần "chưa quy đổi được ra kg").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_department_categories', function (Blueprint $table) {
            $table->decimal('pack_weight_kg', 20, 8)->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_department_categories', function (Blueprint $table) {
            $table->dropColumn('pack_weight_kg');
        });
    }
};
