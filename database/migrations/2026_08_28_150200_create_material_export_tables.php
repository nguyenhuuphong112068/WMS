<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - VẬT TƯ
 *
 * Khác chất chuẩn: vật tư BẮT BUỘC phải có phiếu đề nghị được phê duyệt trước khi lấy
 * ra dùng. Luồng:
 *
 *   Tổ lập ĐỀ NGHỊ (material_request_lists) -> trình ký
 *      -> Trưởng/Phó Phòng ký (BẮT BUỘC)
 *      -> Ban Giám Đốc ký (TUỲ CHỌN, chỉ khi needs_director = 1)
 *      -> approved
 *   Kho CẤP PHÁT từng dòng (material_request_items.status = issued), chỉ định mã lô.
 *   Tổ SỬ DỤNG dòng đã cấp phát -> sinh một bản ghi material_exports (trừ tồn).
 *
 * Riêng LOẠI BỎ (type = cancel) hàng hỏng / hết hạn không phải "sử dụng" nên được lập
 * thẳng trên material_exports, không cần đề nghị.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------- ĐỀ NGHỊ CẤP PHÁT VẬT TƯ (đầu phiếu) --------
        Schema::create('material_request_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();                   // Mã đề nghị (sinh tự động)
            $table->unsignedBigInteger('department_id');            // -> deparments.id
            $table->unsignedBigInteger('group_id');                 // -> groups.id (Tổ đề nghị)
            $table->string('note', 500)->nullable();

            // ---------- Trình ký ----------
            // draft | pending_manager | pending_director | approved | rejected | canceled
            $table->string('app_status', 30)->default('draft');
            $table->boolean('needs_director')->default(false);      // Có cần Ban Giám Đốc ký không
            $table->string('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('manager_signed_by')->nullable();
            $table->timestamp('manager_signed_at')->nullable();
            $table->string('director_signed_by')->nullable();
            $table->timestamp('director_signed_at')->nullable();
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('reject_step', 30)->nullable();          // manager | director
            $table->string('reject_reason', 500)->nullable();

            // ---------- Cấp phát của kho (sau khi approved) ----------
            $table->string('issue_status', 30)->nullable();         // waiting | partial | completed
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'material_request_lists_department_id_index');
            $table->index('group_id', 'material_request_lists_group_id_index');
            $table->index('app_status', 'material_request_lists_app_status_index');
        });

        // -------- DÒNG ĐỀ NGHỊ --------
        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_list_id');          // -> material_request_lists.id
            $table->unsignedBigInteger('category_id')->nullable();  // -> material_categories.id
            $table->string('material_name', 255)->nullable();       // tên tự nhập khi ngoài danh mục
            $table->string('technical_specification', 255)->nullable();
            $table->decimal('requested_amount', 15, 4)->default(0);
            $table->string('requested_unit', 50)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('purpose', 500)->nullable();
            $table->string('note', 500)->nullable();

            // ---------- Cấp phát ----------
            $table->unsignedBigInteger('import_id')->nullable();    // -> material_imports.id
            $table->string('import_code', 50)->nullable();
            $table->decimal('issued_amount', 15, 4)->nullable();
            $table->string('issued_unit', 50)->nullable();
            $table->string('issued_by', 100)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->string('status', 20)->default('pending');       // pending | issued | rejected
            $table->timestamps();

            $table->index('request_list_id', 'material_request_items_parent_index');
            $table->index('category_id', 'material_request_items_category_id_index');
            $table->index('import_id', 'material_request_items_import_id_index');
        });

        // -------- PHIẾU SỬ DỤNG VẬT TƯ (trừ tồn) --------
        Schema::create('material_exports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);                             // = material_imports.code
            $table->unsignedBigInteger('import_id');                // -> material_imports.id
            $table->unsignedBigInteger('department_id');            // -> deparments.id
            $table->unsignedBigInteger('group_id')->nullable();     // -> groups.id
            $table->unsignedBigInteger('request_item_id')->nullable(); // -> material_request_items.id
            $table->decimal('amount', 15, 4)->default(0);
            $table->enum('type', ['export', 'cancel'])->default('export'); // Sử dụng / Loại bỏ
            $table->string('product_name', 255)->nullable();
            $table->string('test_report_no', 100)->nullable();
            $table->string('reason', 500)->nullable();              // lý do loại bỏ
            $table->string('used_by', 255)->nullable();
            $table->string('checked_by', 255)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('code', 'material_exports_code_index');
            $table->index('import_id', 'material_exports_import_id_index');
            $table->index('department_id', 'material_exports_department_id_index');
            $table->index('request_item_id', 'material_exports_request_item_id_index');
        });

        Schema::create('material_export_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_export_id');       // -> material_exports.id
            $table->string('action', 30);

            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('import_id')->nullable();
            $table->decimal('amount', 15, 4)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('test_report_no', 100)->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('used_by', 255)->nullable();
            $table->tinyInteger('status_id')->nullable();

            $table->text('change_note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('material_export_id', 'material_export_histories_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_export_histories');
        Schema::dropIfExists('material_exports');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_request_lists');
    }
};
