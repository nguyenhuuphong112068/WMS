<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỐ LƯỢNG CẦN ĐỀ NGHỊ DỰ TRÙ
 *
 * Modal "Đề nghị dự trù vật tư này?" (tab Vật Tư Của Phòng + dòng đề nghị Sử Dụng Vật Tư)
 * bắt khai thêm số lượng cần dự trù, theo đơn vị tính của phòng. Tab "Danh sách vật tư
 * cần dự trù" điền sẵn số này vào phiếu dự trù / đề nghị liên phòng ban.
 *
 * Nullable vì các dòng ghi nhớ trước đây chưa có số lượng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_watchlist_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->nullable()->after('technical_specification');
        });
    }

    public function down(): void
    {
        Schema::table('material_watchlist_items', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
