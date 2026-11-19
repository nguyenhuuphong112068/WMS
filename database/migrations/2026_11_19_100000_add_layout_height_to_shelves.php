<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mặt bằng kho/phòng: chiều cao thật của kệ/tủ (m). Null = tự tính theo số tầng (0,4 m / tầng).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shelves', 'layout_height')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->decimal('layout_height', 6, 2)->nullable()->after('layout_depth');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shelves', 'layout_height')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->dropColumn('layout_height');
            });
        }
    }
};
