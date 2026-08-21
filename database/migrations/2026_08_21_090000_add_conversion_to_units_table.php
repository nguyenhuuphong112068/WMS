<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỮ LIỆU GỐC - ĐƠN VỊ TÍNH: bổ sung thông tin quy đổi
 *
 * Hai cột này cho phép hệ thống tự quy đổi số lượng giữa các đơn vị:
 *
 * unit_group     : nhóm đơn vị - mass (khối lượng) | volume (thể tích) | count (đếm, bao bì)
 * factor_to_base : hệ số quy về đơn vị gốc của nhóm (g cho mass, ml cho volume, 1 cho count)
 *                  ví dụ kg -> mass, factor 1000 ; L -> volume, factor 1000
 *
 * Quy đổi trong cùng nhóm  : nhân/chia hệ số.
 * Quy đổi mass <-> volume  : cần thêm tỉ trọng d (g/ml) của hoá chất,
 *                            khối lượng (g) = thể tích (ml) x d
 *
 * Danh sách nhóm khai báo tại config/unit.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('unit_group', 20)->default('count')->after('name');
            $table->decimal('factor_to_base', 18, 6)->default(1)->after('unit_group');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['unit_group', 'factor_to_base']);
        });
    }
};
