<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DỰ TRÙ - VẬT TƯ
 *
 * Song song với standard_estimates. Cùng luồng trình ký 2 bước khai báo ở
 * config/estimate.php (draft -> pending_manager -> pending_director -> approved).
 * Khác chất chuẩn: vật tư không có "nhóm chuẩn" nên bỏ cột group_key.
 *
 * Sau khi Ban Giám Đốc duyệt, phiếu TỰ đánh dấu đã tiếp nhận (reception_status =
 * received) - không đi qua màn hình tiếp nhận nào.
 *
 * code : <DeptShortName><yymmdd>.<NN> - xem MaterialEstimateController::nextCode().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_estimates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('department_id');
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->string('note', 500)->nullable();

            // ---------- Trình ký ----------
            $table->string('app_status', 30)->default('draft');
            $table->string('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('manager_signed_by')->nullable();
            $table->timestamp('manager_signed_at')->nullable();
            $table->string('director_signed_by')->nullable();
            $table->timestamp('director_signed_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('reject_step', 30)->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->string('cancel_reason', 500)->nullable();

            // ---------- Tiếp nhận (tự đánh dấu khi duyệt) ----------
            $table->string('reception_status', 30)->nullable();
            $table->string('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('reception_note', 500)->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'material_estimates_department_id_index');
            $table->index('app_status', 'material_estimates_app_status_index');
            $table->index(['year', 'month'], 'material_estimates_period_index');
        });

        Schema::create('material_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_estimate_id');
            $table->unsignedBigInteger('category_id')->nullable();   // -> material_categories.id
            $table->string('material_name', 255)->nullable();        // tự nhập khi ngoài danh mục
            $table->text('technical_information')->nullable();
            $table->text('purpose')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('promised_date')->nullable();
            $table->date('fulfilled_date')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('material_estimate_id', 'material_estimate_items_parent_index');
            $table->index('category_id', 'material_estimate_items_category_id_index');
        });

        Schema::create('material_estimate_item_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_estimate_item_id');
            $table->decimal('amount', 15, 4)->default(0);
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->date('for_month_year');
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('material_estimate_item_id', 'material_estimate_amounts_item_index');
            $table->index('for_month_year', 'material_estimate_amounts_period_index');
        });

        Schema::create('material_estimate_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_estimate_id');
            $table->string('action', 50);
            $table->string('step', 30)->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->string('note', 500)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('material_estimate_id', 'material_estimate_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_estimate_histories');
        Schema::dropIfExists('material_estimate_item_amounts');
        Schema::dropIfExists('material_estimate_items');
        Schema::dropIfExists('material_estimates');
    }
};
