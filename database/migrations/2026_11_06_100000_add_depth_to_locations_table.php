<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vị trí trong một tầng là lưới 2 chiều giống kệ thật: `position` = ô thứ mấy theo chiều ngang
 * (A, B, C...), `depth` = dãy thứ mấy tính từ cửa vào trong (1 sát cửa). Vị trí cũ = dãy 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('locations', 'depth')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedSmallInteger('depth')->default(1)->after('position');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'depth')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('depth');
            });
        }
    }
};
