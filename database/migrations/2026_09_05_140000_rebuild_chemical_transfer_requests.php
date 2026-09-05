<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - ĐỀ NGHỊ CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN (mô hình 1 bước)
 *
 * Thay hẳn cơ chế 3 bước cũ (đề nghị 1-dòng -> đồng ý -> lập phiếu transfer -> phòng
 * nhận bấm Nhận ở màn Nhập Hoá Chất) bằng 1 bước cấp phát duy nhất, giống hệt "Đề nghị
 * cấp phát chuẩn liên phòng ban" (standard_transfer_requests/standard_transfer_items):
 * B chọn lô của mình + cấp phát trực tiếp là trừ tồn B và tạo tồn mới cho A ngay trong
 * một transaction.
 *
 * Bảng chemical_transfer_requests cũ (1 dòng = 1 hoá chất, app_status pending/accepted/
 * rejected) được xoá và tạo lại làm bảng HEADER; chi tiết từng hoá chất chuyển sang bảng
 * mới chemical_transfer_items. Đã xác nhận qua tinker: 0 dòng dữ liệu ở bảng cũ, ở
 * chemical_exports.type='transfer', và ở chemical_imports.source_export_id - an toàn để
 * xoá hẳn, không cần chuyển dữ liệu.
 *
 * chemical_transfer_requests.department_id    : phòng ĐỀ NGHỊ - đang cần hoá chất (A).
 * chemical_transfer_requests.to_department_id : phòng ĐƯỢC ĐỀ NGHỊ - đang giữ hoá chất (B).
 *
 * chemical_transfer_items: mỗi dòng là 1 hoá chất cần. Khi B cấp phát: chọn phiếu nhập cụ
 * thể của B (import_id) + chọn vị trí lưu của phòng A (dest_location_id) rồi ghi
 * new_import_id trỏ sang dòng chemical_imports vừa tạo cho A.
 *
 * chemical_exports.type mở rộng thêm 'transfer_out' (thay cho 'transfer' cũ - bỏ hẳn vì
 * không còn dữ liệu và cơ chế 2 bước không còn dùng nữa). transfer_item_id trỏ ngược
 * sang dòng đề nghị đã tạo ra phiếu này. Các cột received_import_id/received_at/
 * received_by/rejected_at/rejected_by/reject_reason chỉ phục vụ bước "phòng nhận bấm
 * Nhận" / "từ chối nhận" của cơ chế cũ - bỏ luôn vì cơ chế mới tạo tồn cho A ngay lập
 * tức, không có bước nhận/từ chối riêng.
 *
 * chemical_imports.transfer_item_id trỏ ngược sang dòng đề nghị đã tạo ra lô này (đi kèm
 * source_export_id đã có sẵn từ trước, trỏ sang dòng chemical_exports type=transfer_out).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chemical_transfer_requests');

        Schema::create('chemical_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('department_id');    // Phòng đề nghị (A - cần hoá chất)
            $table->unsignedBigInteger('to_department_id'); // Phòng được đề nghị (B - đang giữ hoá chất)
            $table->string('status', 20)->default('pending'); // draft|pending|partial|completed|rejected|canceled
            $table->text('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'chem_transfer_requests_department_id_index');
            $table->index('to_department_id', 'chem_transfer_requests_to_department_id_index');
        });

        Schema::create('chemical_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_request_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('requested_amount', 15, 4)->default(0);
            $table->string('requested_unit', 50)->nullable();
            $table->text('note')->nullable();

            $table->string('status', 20)->default('pending'); // pending|issued|rejected
            $table->string('reject_note', 500)->nullable();

            // Phiếu nhập nguồn được B chọn để cấp
            $table->unsignedBigInteger('import_id')->nullable();
            $table->string('import_code', 50)->nullable();
            $table->decimal('issued_amount', 15, 4)->nullable();
            $table->string('issued_unit', 50)->nullable();
            $table->string('issued_by', 255)->nullable();
            $table->timestamp('issued_at')->nullable();

            // Vị trí lưu của phòng A, B chọn ngay trong lúc cấp phát
            $table->unsignedBigInteger('dest_location_id')->nullable();

            // Dòng chemical_imports mới tạo cho A sau khi cấp phát
            $table->unsignedBigInteger('new_import_id')->nullable();

            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index('transfer_request_id', 'chem_transfer_items_request_id_index');
            $table->index('category_id', 'chem_transfer_items_category_id_index');
            $table->index('import_id', 'chem_transfer_items_import_id_index');
        });

        Schema::table('chemical_exports', function (Blueprint $table) {
            if (! Schema::hasColumn('chemical_exports', 'transfer_item_id')) {
                $table->unsignedBigInteger('transfer_item_id')->nullable()->after('to_department_id');
                $table->index('transfer_item_id', 'chemical_exports_transfer_item_id_index');
            }
        });

        // enum 'type' hiện có 'export','cancel','transfer' - bỏ 'transfer' (0 dữ liệu),
        // thêm 'transfer_out' cho cơ chế 1 bước mới
        DB::statement("ALTER TABLE chemical_exports MODIFY type ENUM('export','cancel','transfer_out') DEFAULT 'export'");

        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->dropColumn(['received_import_id', 'received_at', 'received_by', 'rejected_at', 'rejected_by', 'reject_reason']);
        });

        Schema::table('chemical_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('chemical_imports', 'transfer_item_id')) {
                $table->unsignedBigInteger('transfer_item_id')->nullable()->after('source_export_id');
                $table->index('transfer_item_id', 'chemical_imports_transfer_item_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chemical_imports', function (Blueprint $table) {
            if (Schema::hasColumn('chemical_imports', 'transfer_item_id')) {
                $table->dropIndex('chemical_imports_transfer_item_id_index');
                $table->dropColumn('transfer_item_id');
            }
        });

        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->unsignedBigInteger('received_import_id')->nullable()->after('to_department_id');
            $table->dateTime('received_at')->nullable()->after('received_import_id');
            $table->string('received_by', 255)->nullable()->after('received_at');
            $table->dateTime('rejected_at')->nullable()->after('received_by');
            $table->string('rejected_by', 255)->nullable()->after('rejected_at');
            $table->string('reject_reason', 500)->nullable()->after('rejected_by');

            $table->index('received_import_id', 'chemical_exports_received_import_id_index');
        });

        DB::statement("ALTER TABLE chemical_exports MODIFY type ENUM('export','cancel','transfer') DEFAULT 'export'");

        Schema::table('chemical_exports', function (Blueprint $table) {
            if (Schema::hasColumn('chemical_exports', 'transfer_item_id')) {
                $table->dropIndex('chemical_exports_transfer_item_id_index');
                $table->dropColumn('transfer_item_id');
            }
        });

        Schema::dropIfExists('chemical_transfer_items');
        Schema::dropIfExists('chemical_transfer_requests');

        // Tái tạo bảng chemical_transfer_requests theo đúng cấu trúc cũ (1 dòng = 1 hoá chất)
        Schema::create('chemical_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('to_department_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('amount', 15, 4);
            $table->date('needed_date')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('app_status', 20)->default('pending');
            $table->string('response_note', 500)->nullable();
            $table->string('responded_by', 255)->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->unsignedBigInteger('export_id')->nullable();
            $table->string('requested_by', 255)->nullable();
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('department_id', 'chemical_transfer_requests_department_id_index');
            $table->index('to_department_id', 'chemical_transfer_requests_to_department_id_index');
            $table->index('export_id', 'chemical_transfer_requests_export_id_index');
        });
    }
};
