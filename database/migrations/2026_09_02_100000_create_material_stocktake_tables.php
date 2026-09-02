<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TỒN - KIỂM KÊ KHO VẬT TƯ THEO CHU KỲ 3 THÁNG 1 LẦN
 *
 * Kỳ kiểm kê là một QUÝ dương lịch (Q1 = 01-03, Q2 = 04-06, Q3 = 07-09, Q4 = 10-12).
 * Mỗi phòng ban, mỗi quý mở ĐÚNG MỘT phiếu kiểm kê (period_start = ngày đầu quý).
 * Luồng:
 *
 *      Mở phiếu   -> chốt danh sách mã xuất nhập còn tồn tại thời điểm mở,
 *                    ghi lại tồn sổ sách (system_amount) của từng mã.
 *      Đếm thực tế -> nhập actual_amount cho từng dòng, hệ thống tính diff_amount.
 *      Chốt phiếu  -> dòng nào lệch thì ghi thêm một dòng material_balancings để kéo
 *                    tồn sổ sách về đúng số đếm được. Dòng vượt hạn mức cân đối 5%
 *                    KHÔNG tự ghi, đánh dấu balancing_skipped = 1 để xử lý riêng.
 *
 * state     : counting = đang kiểm kê, completed = đã chốt. Phiếu đã chốt không sửa.
 * status_id : 1 = có hiệu lực, 0 = đã huỷ. Không xoá cứng.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------- ĐẦU PHIẾU KIỂM KÊ (mỗi quý một phiếu / phòng ban) --------
        Schema::create('material_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);                          // KKVT-<mã phòng>-YYYYQn
            $table->unsignedBigInteger('department_id');         // -> deparments.id
            $table->date('period_start');                        // Ngày đầu quý của kỳ kiểm kê
            $table->date('from_date');                           // = period_start
            $table->date('to_date');                             // Ngày cuối quý của kỳ
            $table->string('state', 20)->default('counting');    // counting | completed
            $table->string('note', 500)->nullable();

            $table->string('opened_by', 255)->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->string('completed_by', 255)->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'material_stocktakes_department_id_index');
            $table->index('period_start', 'material_stocktakes_period_start_index');
            $table->index('state', 'material_stocktakes_state_index');
        });

        // -------- DÒNG KIỂM KÊ (một mã xuất nhập = một dòng đếm) --------
        Schema::create('material_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stocktake_id');           // -> material_stocktakes.id
            $table->unsignedBigInteger('import_id');              // -> material_imports.id
            $table->string('code', 50);                           // = material_imports.code
            $table->unsignedBigInteger('category_id')->nullable(); // -> material_categories.id
            $table->unsignedBigInteger('location_id')->nullable(); // -> locations.id
            $table->unsignedBigInteger('department_id');           // -> deparments.id

            $table->decimal('system_amount', 15, 4)->default(0);   // Tồn sổ sách lúc mở phiếu
            $table->decimal('actual_amount', 15, 4)->nullable();   // Số đếm thực tế
            $table->decimal('diff_amount', 15, 4)->nullable();     // actual - system
            $table->string('note', 500)->nullable();

            $table->string('counted_by', 255)->nullable();
            $table->dateTime('counted_at')->nullable();

            // Kết quả xử lý chênh lệch lúc chốt phiếu
            $table->unsignedBigInteger('balancing_id')->nullable(); // -> material_balancings.id
            $table->boolean('balancing_skipped')->default(false);   // Lệch vượt hạn mức, chưa xử lý
            $table->string('balancing_note', 500)->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('stocktake_id', 'material_stocktake_items_stocktake_id_index');
            $table->index('import_id', 'material_stocktake_items_import_id_index');
            $table->index('department_id', 'material_stocktake_items_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_stocktake_items');
        Schema::dropIfExists('material_stocktakes');
    }
};
