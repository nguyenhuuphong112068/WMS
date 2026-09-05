<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỀ NGHỊ CẤP PHÁT CHUẨN LIÊN PHÒNG BAN
 *
 * Khác quy trình nội bộ (standard_request_lists/items - chỉ RESERVE ống cho Tổ, tồn kho
 * chưa đổi cho tới khi ai đó lập phiếu Sử Dụng thật), đề nghị liên phòng ban CHUYỂN TỒN
 * THẬT ngay khi cấp phát: trừ tồn phòng nguồn (standard_exports), cộng tồn phòng nhận
 * bằng một dòng standard_imports MỚI. Cùng ý tưởng với "Chuyển kho" đã có cho hoá chất
 * (chemical_transfer_requests, chemical_exports.to_department_id, chemical_imports.source_export_id)
 * nhưng gộp 3 bước (đồng ý -> lập phiếu chuyển -> nhận hàng) thành 1 bước cấp phát duy nhất.
 *
 * standard_transfer_requests.department_id    : phòng ĐỀ NGHỊ - đang cần chuẩn (gọi là A).
 * standard_transfer_requests.to_department_id : phòng ĐƯỢC ĐỀ NGHỊ - đang giữ chuẩn (gọi là B).
 *
 * standard_transfer_items: mỗi dòng là 1 chất chuẩn cần. Khi B cấp phát: chọn ống cụ thể
 * của B (import_id) + điền lại thông tin RIÊNG CỦA PHÒNG A cho ống mới sẽ nhập
 * (dest_location_id/dest_purpose_id/dest_weight_controlled/dest_standard_form/dest_requires_aliquot),
 * rồi ghi new_import_id trỏ sang dòng standard_imports vừa tạo cho A.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('standard_transfer_requests')) {
            Schema::create('standard_transfer_requests', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->unsignedBigInteger('department_id');    // Phòng đề nghị (A - cần chuẩn)
                $table->unsignedBigInteger('to_department_id'); // Phòng được đề nghị (B - đang giữ chuẩn)
                $table->string('status', 20)->default('pending'); // draft|pending|partial|completed|rejected|canceled
                $table->text('note')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index('department_id', 'std_transfer_requests_department_id_index');
                $table->index('to_department_id', 'std_transfer_requests_to_department_id_index');
            });
        }

        if (! Schema::hasTable('standard_transfer_items')) {
            Schema::create('standard_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transfer_request_id');
                $table->unsignedBigInteger('category_id');
                $table->decimal('requested_amount', 15, 4)->default(0);
                $table->string('requested_unit', 50)->nullable();
                $table->unsignedBigInteger('purpose_id')->nullable(); // Chỉ tiêu kiểm A đề nghị (gợi ý ban đầu)
                $table->text('note')->nullable();

                $table->string('status', 20)->default('pending'); // pending|issued|rejected
                $table->string('reject_note', 500)->nullable();

                // Ống nguồn được B chọn để cấp
                $table->unsignedBigInteger('import_id')->nullable();
                $table->string('import_code', 50)->nullable();
                $table->decimal('issued_amount', 15, 4)->nullable();
                $table->string('issued_unit', 50)->nullable();
                $table->string('issued_by', 255)->nullable();
                $table->timestamp('issued_at')->nullable();

                // Thông tin riêng của phòng A, B điền lại ngay trước khi tạo dòng nhập mới
                $table->unsignedBigInteger('dest_location_id')->nullable();
                $table->unsignedBigInteger('dest_purpose_id')->nullable();
                $table->tinyInteger('dest_weight_controlled')->default(0);
                $table->string('dest_standard_form', 50)->nullable();
                $table->tinyInteger('dest_requires_aliquot')->default(0);

                // Dòng standard_imports mới tạo cho A sau khi cấp phát
                $table->unsignedBigInteger('new_import_id')->nullable();

                $table->boolean('active')->default(true)->index();
                $table->timestamps();

                $table->index('transfer_request_id', 'std_transfer_items_request_id_index');
                $table->index('category_id', 'std_transfer_items_category_id_index');
                $table->index('import_id', 'std_transfer_items_import_id_index');
            });
        }

        if (Schema::hasTable('standard_exports')) {
            Schema::table('standard_exports', function (Blueprint $table) {
                if (! Schema::hasColumn('standard_exports', 'to_department_id')) {
                    $table->unsignedBigInteger('to_department_id')->nullable()->after('department_id');
                }
                if (! Schema::hasColumn('standard_exports', 'transfer_item_id')) {
                    $table->unsignedBigInteger('transfer_item_id')->nullable()->after('to_department_id');
                    $table->index('transfer_item_id', 'standard_exports_transfer_item_id_index');
                }
            });

            // enum 'type' hiện chỉ có 'export','cancel' - mở rộng thêm 'transfer_out'
            DB::statement("ALTER TABLE standard_exports MODIFY type ENUM('export','cancel','transfer_out') DEFAULT 'export'");
        }

        if (Schema::hasTable('standard_imports')) {
            Schema::table('standard_imports', function (Blueprint $table) {
                if (! Schema::hasColumn('standard_imports', 'source_import_id')) {
                    $table->unsignedBigInteger('source_import_id')->nullable()->after('category_id');
                }
                if (! Schema::hasColumn('standard_imports', 'transfer_item_id')) {
                    $table->unsignedBigInteger('transfer_item_id')->nullable()->after('source_import_id');
                    $table->index('transfer_item_id', 'standard_imports_transfer_item_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('standard_imports')) {
            Schema::table('standard_imports', function (Blueprint $table) {
                if (Schema::hasColumn('standard_imports', 'transfer_item_id')) {
                    $table->dropIndex('standard_imports_transfer_item_id_index');
                    $table->dropColumn('transfer_item_id');
                }
                if (Schema::hasColumn('standard_imports', 'source_import_id')) {
                    $table->dropColumn('source_import_id');
                }
            });
        }

        if (Schema::hasTable('standard_exports')) {
            DB::statement("ALTER TABLE standard_exports MODIFY type ENUM('export','cancel') DEFAULT 'export'");

            Schema::table('standard_exports', function (Blueprint $table) {
                if (Schema::hasColumn('standard_exports', 'transfer_item_id')) {
                    $table->dropIndex('standard_exports_transfer_item_id_index');
                    $table->dropColumn('transfer_item_id');
                }
                if (Schema::hasColumn('standard_exports', 'to_department_id')) {
                    $table->dropColumn('to_department_id');
                }
            });
        }

        Schema::dropIfExists('standard_transfer_items');
        Schema::dropIfExists('standard_transfer_requests');
    }
};
