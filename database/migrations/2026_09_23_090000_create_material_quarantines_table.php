<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LOẠI BỎ VẬT TƯ HỎNG - QUY TRÌNH 3 BƯỚC (thay cho phiếu "Loại bỏ" lập thẳng trước đây)
 *
 *   1. quarantined : CÁCH LY CHỜ QUYẾT ĐỊNH. Hàng vẫn nằm trong kho (tồn sổ sách giữ nguyên)
 *                    nhưng bị giữ chỗ - không cấp phát / chuyển đi được. Có quyết định thì
 *                    có thể TRẢ VỀ KHO (returned) để dùng lại.
 *   2. removed     : LOẠI BỎ. Sinh một material_exports type = cancel (quarantine_id trỏ về
 *                    đây) - trừ tồn thật, KHÔNG quay lại kho được nữa.
 *   3. destroyed   : HUỶ. Ghi nhận đã huỷ thực tế (phương pháp, ngày huỷ). Không động tồn.
 *
 *   returned       : nhánh kết thúc của bước 1 khi quyết định cho hàng quay lại kho.
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_quarantine', 'Cách Ly / Huỷ Vật Tư Hỏng', 'Lập phiếu cách ly vật tư hỏng chờ quyết định và ghi nhận huỷ vật tư đã loại bỏ'],
        [4, 'export_material_quarantine_decide', 'Quyết Định Loại Bỏ Vật Tư Hỏng', 'Quyết định loại bỏ hoặc trả về kho vật tư đang cách ly (ký xác nhận bằng mật khẩu)'],
    ];

    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        Schema::create('material_quarantines', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                              // CL-<id phòng>-<đuôi ngẫu nhiên>
            $table->unsignedBigInteger('department_id');                   // -> deparments.id
            $table->unsignedBigInteger('import_id');                       // -> material_imports.id
            $table->decimal('amount', 15, 4);
            $table->string('reason', 500);                                 // Lý do cách ly (hỏng gì)
            $table->string('app_status', 20)->default('quarantined');      // quarantined | returned | removed | destroyed

            $table->string('decision_note', 500)->nullable();              // Nội dung quyết định (bước 2)
            $table->string('decided_by')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->unsignedBigInteger('export_id')->nullable();           // -> material_exports.id (type = cancel) khi loại bỏ

            $table->string('destroy_method', 255)->nullable();             // Phương pháp huỷ (bước 3)
            $table->string('destroy_note', 500)->nullable();
            $table->string('destroyed_by')->nullable();
            $table->dateTime('destroyed_at')->nullable();

            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'app_status'], 'material_quarantines_dept_status_index');
            $table->index('import_id', 'material_quarantines_import_index');
        });

        Schema::table('material_exports', function (Blueprint $table) {
            // Phiếu loại bỏ sinh từ bước 2 - khoá không cho sửa / khoá phiếu để khỏi "quay lại kho"
            $table->unsignedBigInteger('quarantine_id')->nullable()->after('request_item_id');
        });

        $now = now();

        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission[1]], [
                'permission_group' => $permission[0],
                'display_name' => $permission[2],
                'description' => $permission[3],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        $roleIds = DB::table('roles')->whereIn('name', self::GRANT_TO_ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_column(self::PERMISSIONS, 1))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::table('material_exports', function (Blueprint $table) {
            $table->dropColumn('quarantine_id');
        });

        Schema::dropIfExists('material_quarantines');
    }
};
