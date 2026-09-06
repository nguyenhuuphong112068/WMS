<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỀ NGHỊ & CẤP PHÁT VẬT TƯ LIÊN PHÒNG BAN
 *
 * Bản sao nghiệp vụ của "Đề nghị chuyển hoá chất liên phòng ban"
 * (rebuild_chemical_transfer_requests + add_receive_step_to_transfer_items) cho vật tư,
 * mô hình 3 bước:
 *
 *   1. Phòng A (đang thiếu) lập đề nghị gửi phòng B (đang giữ vật tư).
 *   2. Phòng B chọn mã xuất nhập của mình + số lượng rồi Cấp phát: TRỪ TỒN B ngay bằng
 *      một dòng material_exports type = 'transfer_out'; item sang status 'issued'
 *      (chờ nhận) - CHƯA có tồn cho A.
 *   3. Phòng A chọn định khu của phòng mình rồi bấm Nhận: mới thật sự tạo một dòng
 *      material_imports MỚI cho A (item sang 'received'). A cũng có thể Từ chối nhận
 *      (item sang 'returned') - khoá dòng export transfer_out (status_id = 0) là tồn
 *      của B tự hoàn lại, vì mọi công thức tồn chỉ cộng export status_id = 1.
 *
 * Khác đề nghị NỘI BỘ (material_request_lists/items - Tổ đề nghị, Trưởng phòng ký, kho
 * cấp phát trong cùng phòng): đề nghị liên phòng ban không qua trình ký, và hàng chuyển
 * đi thành TỒN THẬT của phòng nhận chứ không phải hàng đã đem sử dụng.
 *
 * material_transfer_requests.department_id    : phòng ĐỀ NGHỊ - đang cần vật tư (A).
 * material_transfer_requests.to_department_id : phòng ĐƯỢC ĐỀ NGHỊ - đang giữ vật tư (B).
 *
 * Vật tư không có khái niệm lô nguyên / lô lẻ (chỉ chemical_imports.is_partial_lot) nên
 * material_transfer_items không cần cột đó - giống standard_transfer_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('material_transfer_requests')) {
            Schema::create('material_transfer_requests', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->unsignedBigInteger('department_id');    // Phòng đề nghị (A - cần vật tư)
                $table->unsignedBigInteger('to_department_id'); // Phòng được đề nghị (B - đang giữ vật tư)
                $table->string('status', 20)->default('pending'); // draft|pending|partial|completed|rejected|canceled
                $table->text('note')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();

                $table->index('department_id', 'mat_transfer_requests_department_id_index');
                $table->index('to_department_id', 'mat_transfer_requests_to_department_id_index');
            });
        }

        if (! Schema::hasTable('material_transfer_items')) {
            Schema::create('material_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transfer_request_id');
                $table->unsignedBigInteger('category_id');
                $table->decimal('requested_amount', 15, 4)->default(0);
                $table->string('requested_unit', 50)->nullable();
                $table->text('note')->nullable();

                // pending|issued|received|returned|rejected
                $table->string('status', 20)->default('pending');
                $table->string('reject_note', 500)->nullable();

                // Mã xuất nhập nguồn được B chọn để cấp
                $table->unsignedBigInteger('import_id')->nullable();
                $table->string('import_code', 50)->nullable();
                $table->decimal('issued_amount', 15, 4)->nullable();
                $table->string('issued_unit', 50)->nullable();
                $table->string('issued_by', 255)->nullable();
                $table->timestamp('issued_at')->nullable();

                // Định khu của phòng A, do chính A chọn ở bước Nhận
                $table->unsignedBigInteger('dest_location_id')->nullable();

                // Dòng material_imports mới tạo cho A sau khi A bấm Nhận
                $table->unsignedBigInteger('new_import_id')->nullable();
                $table->string('received_by', 255)->nullable();
                $table->timestamp('received_at')->nullable();

                // A từ chối nhận - hoàn tồn lại cho B
                $table->string('return_note', 500)->nullable();
                $table->string('returned_by', 255)->nullable();
                $table->timestamp('returned_at')->nullable();

                $table->boolean('active')->default(true)->index();
                $table->timestamps();

                $table->index('transfer_request_id', 'mat_transfer_items_request_id_index');
                $table->index('category_id', 'mat_transfer_items_category_id_index');
                $table->index('import_id', 'mat_transfer_items_import_id_index');
            });
        }

        Schema::table('material_exports', function (Blueprint $table) {
            if (! Schema::hasColumn('material_exports', 'to_department_id')) {
                $table->unsignedBigInteger('to_department_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('material_exports', 'transfer_item_id')) {
                $table->unsignedBigInteger('transfer_item_id')->nullable()->after('request_item_id');
                $table->index('transfer_item_id', 'material_exports_transfer_item_id_index');
            }
        });

        // enum 'type' hiện có 'export','cancel' - thêm 'transfer_out' cho phiếu cấp phát
        // liên phòng ban (không chọn được ở form Sử Dụng, chỉ sinh qua transferIssueStore)
        DB::statement("ALTER TABLE material_exports MODIFY type ENUM('export','cancel','transfer_out') DEFAULT 'export'");

        Schema::table('material_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('material_imports', 'source_export_id')) {
                $table->unsignedBigInteger('source_export_id')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('material_imports', 'transfer_item_id')) {
                $table->unsignedBigInteger('transfer_item_id')->nullable()->after('source_export_id');
                $table->index('transfer_item_id', 'material_imports_transfer_item_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_imports', function (Blueprint $table) {
            if (Schema::hasColumn('material_imports', 'transfer_item_id')) {
                $table->dropIndex('material_imports_transfer_item_id_index');
                $table->dropColumn('transfer_item_id');
            }
            if (Schema::hasColumn('material_imports', 'source_export_id')) {
                $table->dropColumn('source_export_id');
            }
        });

        DB::statement("ALTER TABLE material_exports MODIFY type ENUM('export','cancel') DEFAULT 'export'");

        Schema::table('material_exports', function (Blueprint $table) {
            if (Schema::hasColumn('material_exports', 'transfer_item_id')) {
                $table->dropIndex('material_exports_transfer_item_id_index');
                $table->dropColumn('transfer_item_id');
            }
            if (Schema::hasColumn('material_exports', 'to_department_id')) {
                $table->dropColumn('to_department_id');
            }
        });

        Schema::dropIfExists('material_transfer_items');
        Schema::dropIfExists('material_transfer_requests');
    }
};
