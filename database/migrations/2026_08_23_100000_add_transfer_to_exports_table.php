<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SỬ DỤNG - THÊM LOẠI PHIẾU "CHUYỂN KHO"
 *
 * Ngoài 'export' (sử dụng) và 'cancel' (huỷ bỏ), phiếu sử dụng có thêm loại
 * 'transfer': chuyển hoá chất từ kho phòng ban này sang phòng ban khác.
 *
 * Luồng 2 bước:
 * 1. Phòng GỬI lập phiếu chuyển -> trừ tồn của phòng gửi ngay, hàng ở trạng thái
 *    "chờ nhận", chưa thuộc kho phòng nhận.
 * 2. Phòng NHẬN vào màn hình Nhập Hoá Chất bấm Nhận, khai định khu của phòng mình
 *    -> lúc đó mới sinh một dòng imports mới (mã có hậu tố -CK) và cộng vào tồn.
 *
 * to_department_id   : -> deparments.id, phòng ban nhận hàng.
 * received_import_id : -> imports.id, dòng nhập được sinh ra ở phòng nhận.
 *                      Còn NULL nghĩa là hàng vẫn đang chờ nhận. Đã có giá trị thì
 *                      phòng gửi không được sửa / khoá phiếu chuyển nữa, vì phòng
 *                      nhận đã ghi tồn theo số lượng này.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cột enum phải đổi bằng câu lệnh thẳng, Schema builder không sửa được enum
        DB::statement("ALTER TABLE `chemical_exports` MODIFY `type` ENUM('export','cancel','transfer') NOT NULL DEFAULT 'export'");

        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->unsignedBigInteger('to_department_id')->nullable()->after('type');
            $table->unsignedBigInteger('received_import_id')->nullable()->after('to_department_id');
            $table->dateTime('received_at')->nullable()->after('received_import_id');
            $table->string('received_by', 255)->nullable()->after('received_at');

            $table->index('to_department_id', 'chemical_exports_to_department_id_index');
            $table->index('received_import_id', 'chemical_exports_received_import_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->dropIndex('chemical_exports_to_department_id_index');
            $table->dropIndex('chemical_exports_received_import_id_index');
            $table->dropColumn(['to_department_id', 'received_import_id', 'received_at', 'received_by']);
        });

        DB::statement("ALTER TABLE `chemical_exports` MODIFY `type` ENUM('export','cancel') NOT NULL DEFAULT 'export'");
    }
};
