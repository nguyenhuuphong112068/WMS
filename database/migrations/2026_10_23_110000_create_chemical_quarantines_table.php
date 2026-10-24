<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HOÁ CHẤT HỎNG - QUY TRÌNH 3 BƯỚC NHƯ VẬT TƯ (thay cho phiếu "Loại bỏ" lập thẳng ở Sử Dụng Hoá
 * Chất + đợt xin quyết định huỷ chemical_disposals trước đây)
 *
 *   1. quarantined : CÁCH LY CHỜ QUYẾT ĐỊNH. Bấm [Cách ly] ở Tồn Kho Hoá Chất -> hàng dời sang định
 *                    khu "Chờ Quyết Định", TRỪ TỒN NGAY bằng chemical_exports type = cancel có
 *                    quarantine_id. Quyết định "Trả về kho" (returned) thì khoá dòng xuất - hoàn tồn.
 *   2. removed     : LOẠI BỎ - CHỜ HUỶ. Giữ nguyên dòng xuất, hàng dời sang định khu "Loại Bỏ/Chờ
 *                    Huỷ", không quay lại kho được nữa.
 *   3. destroyed   : HUỶ. Ghi nhận đã huỷ thực tế (phương pháp, ghi chú). Không động tồn.
 *
 * Bỏ luồng huỷ cũ: xoá quyền export_chemical_disposal_* (tab "Hoá chất chờ huỷ"). Bảng
 * chemical_disposals + cột chemical_exports.disposal_id giữ nguyên làm dữ liệu lịch sử.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description, quyền cũ được thừa hưởng] */
    private const PERMISSIONS = [
        [4, 'export_chemical_quarantine', 'Cách Ly / Huỷ Hoá Chất Hỏng', 'Lập phiếu cách ly hoá chất hỏng chờ quyết định và ghi nhận huỷ hoá chất đã loại bỏ', 'export_chemical_disposal_manage'],
        [4, 'export_chemical_quarantine_decide', 'Quyết Định Loại Bỏ Hoá Chất Hỏng', 'Quyết định loại bỏ hoặc trả về kho hoá chất đang cách ly (ký xác nhận bằng mật khẩu)', 'export_chemical_disposal_decide'],
    ];

    private const OLD_PERMISSIONS = [
        'export_chemical_disposal_view',
        'export_chemical_disposal_manage',
        'export_chemical_disposal_decide',
    ];

    public function up(): void
    {
        Schema::create('chemical_quarantines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                              // CLHC-<id phòng>-<đuôi ngẫu nhiên>
            $table->unsignedBigInteger('department_id');                   // -> deparments.id
            $table->unsignedBigInteger('import_id');                       // -> chemical_imports.id
            $table->decimal('amount', 15, 4);
            $table->string('reason', 500);                                 // Lý do cách ly (hỏng / hết hạn...)
            $table->unsignedBigInteger('pending_location_id')->nullable(); // -> locations.id, zone_type = pending
            $table->string('app_status', 20)->default('quarantined');      // quarantined | returned | removed | destroyed

            $table->string('decision_note', 500)->nullable();              // Nội dung quyết định (bước 2)
            $table->unsignedBigInteger('rejected_location_id')->nullable(); // -> locations.id, zone_type = rejected
            $table->unsignedBigInteger('returned_location_id')->nullable(); // -> locations.id khi trả về kho
            $table->string('decided_by')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->unsignedBigInteger('export_id')->nullable();           // -> chemical_exports.id (type = cancel) trừ tồn

            $table->string('destroy_method', 255)->nullable();             // Phương pháp huỷ (bước 3)
            $table->string('destroy_note', 500)->nullable();
            $table->string('destroyed_by')->nullable();
            $table->dateTime('destroyed_at')->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'app_status'], 'chemical_quarantines_dept_status_index');
            $table->index('import_id', 'chemical_quarantines_import_index');
        });

        Schema::table('chemical_exports', function (Blueprint $table) {
            // Dòng trừ tồn sinh từ phiếu cách ly - không sửa / khoá ở Sổ sử dụng
            $table->unsignedBigInteger('quarantine_id')->nullable()->after('disposal_id');
            $table->index('quarantine_id', 'chemical_exports_quarantine_id_index');
        });

        $now = now();
        $adminRoleIds = DB::table('roles')->where('name', 'Admin')->pluck('id');

        foreach (self::PERMISSIONS as [$group, $name, $display, $description, $inherit]) {
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'permission_group' => $group,
                'display_name' => $display,
                'description' => $description,
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            $permissionId = DB::table('permissions')->where('name', $name)->value('id');
            $oldId = DB::table('permissions')->where('name', $inherit)->value('id');

            // Ai đang lập / quyết định đợt huỷ cũ thì giữ được việc tương ứng ở luồng mới
            $roleIds = $adminRoleIds
                ->merge($oldId ? DB::table('role_permission')->where('permission_id', $oldId)->pluck('role_id') : [])
                ->unique();

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }

            if ($oldId) {
                // Cấp riêng / chặn riêng theo từng user cũng chuyển sang quyền mới
                foreach (DB::table('user_permission')->where('permission_id', $oldId)->get() as $grant) {
                    DB::table('user_permission')->updateOrInsert(
                        ['user_id' => $grant->user_id, 'permission_id' => $permissionId],
                        ['is_denied' => $grant->is_denied]
                    );
                }
            }
        }

        $oldIds = DB::table('permissions')->whereIn('name', self::OLD_PERMISSIONS)->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('role_permission')->whereIn('permission_id', $oldIds)->delete();
        DB::table('permissions')->whereIn('id', $oldIds)->delete();

        // Quyền xem tab mới "Cách ly - Loại bỏ - Huỷ" (export_chemical_tab_quarantine)
        PageTabs::seed();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_merge(array_column(self::PERMISSIONS, 1), ['export_chemical_tab_quarantine']))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::table('chemical_exports', function (Blueprint $table) {
            $table->dropIndex('chemical_exports_quarantine_id_index');
            $table->dropColumn('quarantine_id');
        });

        Schema::dropIfExists('chemical_quarantines');
    }
};
