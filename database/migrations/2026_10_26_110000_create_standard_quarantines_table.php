<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CHẤT CHUẨN HỎNG - QUY TRÌNH 3 BƯỚC NHƯ HOÁ CHẤT (thay cho phiếu "Loại bỏ" lập thẳng ở Sử Dụng Chất Chuẩn)
 *
 *   1. quarantined : CÁCH LY CHỜ QUYẾT ĐỊNH. Bấm [Cách ly] ở Tồn Kho Chất Chuẩn -> ống dời sang định khu
 *                    "Chờ Quyết Định", TRỪ TỒN NGAY bằng standard_exports type = cancel có quarantine_id.
 *                    Quyết định "Trả về kho" (returned) thì đưa số lượng dòng xuất đó về 0 - hoàn tồn
 *                    (standard_exports không có cột status_id để khoá dòng như hoá chất).
 *   2. removed     : LOẠI BỎ - CHỜ HUỶ. Giữ nguyên dòng xuất, ống dời sang định khu "Loại Bỏ/Chờ Huỷ".
 *   3. destroyed   : HUỶ. Ghi nhận đã huỷ thực tế (phương pháp, ghi chú). Không động tồn.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_standard_quarantine', 'Cách Ly / Huỷ Chất Chuẩn Hỏng', 'Lập phiếu cách ly chất chuẩn hỏng chờ quyết định và ghi nhận huỷ chất chuẩn đã loại bỏ'],
        [4, 'export_standard_quarantine_decide', 'Quyết Định Loại Bỏ Chất Chuẩn Hỏng', 'Quyết định loại bỏ hoặc trả về kho chất chuẩn đang cách ly (ký xác nhận bằng mật khẩu)'],
    ];

    /** Vai trò được cấp sẵn cả hai quyền - cùng nhóm đang cách ly / quyết định hoá chất hỏng + kho chuẩn. */
    private const ROLES = ['Admin', 'Quản Lý Bộ Phân', 'Quản Lý Kho Chất Chuẩn'];

    public function up(): void
    {
        Schema::create('standard_quarantines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                              // CLCC-<id phòng>-<đuôi ngẫu nhiên>
            $table->unsignedBigInteger('department_id');                   // -> deparments.id
            $table->unsignedBigInteger('import_id');                       // -> standard_imports.id
            $table->decimal('amount', 15, 4);
            $table->string('reason', 500);                                 // Lý do cách ly (hỏng / hết hạn...)
            $table->unsignedBigInteger('pending_location_id')->nullable(); // -> locations.id, zone_type = pending
            $table->string('app_status', 20)->default('quarantined');      // quarantined | returned | removed | destroyed

            $table->string('decision_note', 500)->nullable();              // Nội dung quyết định (bước 2)
            $table->unsignedBigInteger('rejected_location_id')->nullable(); // -> locations.id, zone_type = rejected
            $table->unsignedBigInteger('returned_location_id')->nullable(); // -> locations.id khi trả về kho
            $table->string('decided_by')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->unsignedBigInteger('export_id')->nullable();           // -> standard_exports.id (type = cancel) trừ tồn

            $table->string('destroy_method', 255)->nullable();             // Phương pháp huỷ (bước 3)
            $table->string('destroy_note', 500)->nullable();
            $table->string('destroyed_by')->nullable();
            $table->dateTime('destroyed_at')->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'app_status'], 'standard_quarantines_dept_status_index');
            $table->index('import_id', 'standard_quarantines_import_index');
        });

        Schema::table('standard_exports', function (Blueprint $table) {
            // Dòng trừ tồn sinh từ phiếu cách ly - không sửa ở Sổ sử dụng
            $table->unsignedBigInteger('quarantine_id')->nullable()->after('transfer_item_id');
            $table->index('quarantine_id', 'standard_exports_quarantine_id_index');
        });

        $now = now();
        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->pluck('id');

        foreach (self::PERMISSIONS as [$group, $name, $display, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'permission_group' => $group,
                'display_name' => $display,
                'description' => $description,
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            $permissionId = DB::table('permissions')->where('name', $name)->value('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }
        }

        // Quyền xem tab mới "Cách ly - Loại bỏ - Huỷ" (export_standard_tab_quarantine)
        PageTabs::seed();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_merge(array_column(self::PERMISSIONS, 1), ['export_standard_tab_quarantine']))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::table('standard_exports', function (Blueprint $table) {
            $table->dropIndex('standard_exports_quarantine_id_index');
            $table->dropColumn('quarantine_id');
        });

        Schema::dropIfExists('standard_quarantines');
    }
};
