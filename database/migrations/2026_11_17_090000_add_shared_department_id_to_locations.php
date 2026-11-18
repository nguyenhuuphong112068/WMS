<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 17/11/2026 CHIA SẺ VỊ TRÍ CHO BỘ PHẬN KHÁC: kho chung do một phòng quản lý (vd. QC) giao một số ô
 * cho bộ phận khác (vd. Phân xưởng) cất hàng. Mỗi ô giao cho đúng MỘT bộ phận và khi đã giao thì
 * phòng chủ kho không cất hàng vào ô đó nữa (App\Support\LocationShare).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('locations', 'shared_department_id')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedBigInteger('shared_department_id')->nullable()->after('department_id')
                ->comment('Bộ phận được giao dùng ô này (null = chỉ phòng chủ kho dùng)');
            $table->index('shared_department_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('locations', 'shared_department_id')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['shared_department_id']);
            $table->dropColumn('shared_department_id');
        });
    }
};
