<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vị trí chỉ còn định danh bằng MÃ.
 *
 * Cột `name` của bảng locations bị bỏ hẳn: mã vị trí (A01, B02...) đã đủ để
 * nhận biết ô lưu trữ, tên vị trí chỉ là chép lại mã nên không còn ý nghĩa.
 * Ba cấp trên (kho / phòng / kệ) vẫn giữ nguyên tên.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('locations', 'name')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('locations', 'name')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('name', 255)->nullable()->after('code');
            });
        }
    }
};
