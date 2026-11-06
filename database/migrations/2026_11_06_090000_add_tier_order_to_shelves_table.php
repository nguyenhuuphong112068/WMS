<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chiều đánh số tầng của kệ/tủ: bottom_up = Tầng 01 nằm dưới cùng (mặc định cũ),
 * top_down = Tầng 01 nằm trên cùng. Chỉ đổi cách xếp lưới, mã tầng giữ theo position.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shelves', 'tier_order')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->string('tier_order', 10)->default('bottom_up')->after('warehouse_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shelves', 'tier_order')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->dropColumn('tier_order');
            });
        }
    }
};
