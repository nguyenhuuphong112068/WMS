<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VẬT TƯ CẦN DỰ TRÙ (GHI NHỚ THỦ CÔNG)
 *
 * Người dùng bấm "Ghi nhớ vật tư cần dự trù" ngay trên dòng đề nghị của modal Đề Nghị
 * Thường Quy / Theo ĐG Rủi Ro (SỬ DỤNG VẬT TƯ) để đánh dấu một vật tư cần được khai dự trù,
 * không phụ thuộc đề nghị đó có được lưu/trình ký hay không.
 *
 * Tab "Danh sách vật tư cần dự trù" (DỰ TRÙ VẬT TƯ) gộp các dòng ở đây với các vật tư đang
 * tồn dưới min_stock (material_department_categories) để hiển thị chung, xem
 * App\Support\MaterialWatchlist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');                  // -> deparments.id
            $table->unsignedBigInteger('category_id')->nullable();        // -> material_categories.id, null = vật tư ngoài danh mục
            $table->string('material_name')->nullable();                  // Tên khi ngoài danh mục
            $table->string('technical_specification')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('source_type')->nullable();                    // regular | risk_assessment
            $table->tinyInteger('status_id')->default(1);                 // 1 = đang theo dõi, 0 = đã bỏ ghi nhớ
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['department_id', 'category_id'], 'material_watchlist_items_unique');
            $table->index(['department_id', 'status_id'], 'material_watchlist_items_dept_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_watchlist_items');
    }
};
