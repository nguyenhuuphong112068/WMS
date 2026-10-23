<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ĐỀ NGHỊ HIỆU CHUẨN TRƯỚC KHI CẤP PHÁT (vật tư phân loại "Cần Hiệu Chuẩn Trước Khi Dùng")
 *
 * Cấp phát nội bộ vật tư cần hiệu chuẩn KHÔNG cấp ngay: kho chọn mã xuất nhập + số lượng rồi
 * "Đề nghị hiệu chuẩn" -> QA xác nhận Đạt / Không đạt (ký mật khẩu) -> chỉ phần ĐẠT mới được cấp.
 * Một lần đề nghị = một mã phiếu (code), mỗi mã xuất nhập một dòng (các dòng cùng code).
 *
 *   pending  : chờ QA hiệu chuẩn - giữ chỗ tồn (amount), không cấp / chuyển / cách ly được
 *   passed   : đạt - vẫn giữ chỗ cho đúng dòng đề nghị, phần chưa cấp = amount - issued_amount
 *   issued   : đã cấp hết phần đạt
 *   failed   : không đạt - nhả giữ chỗ, hàng tự sang Cách ly (quarantine_id)
 *   canceled : huỷ đề nghị / kho từ chối cấp dòng đề nghị - nhả giữ chỗ
 */
return new class extends Migration
{
    /** [permission_group, name, display_name, description] */
    private const PERMISSIONS = [
        [4, 'export_material_calibration_confirm', 'Xác Nhận Hiệu Chuẩn Vật Tư', 'QA xác nhận kết quả Đạt / Không đạt cho đề nghị hiệu chuẩn vật tư trước khi cấp phát (ký xác nhận bằng mật khẩu)'],
    ];

    private const GRANT_TO_ROLES = ['Admin'];

    public function up(): void
    {
        Schema::create('material_calibrations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40);                                    // HCN-<id phòng>-<đuôi ngẫu nhiên>, chung cho các dòng một lần đề nghị
            $table->unsignedBigInteger('department_id');                   // phòng đề nghị (phòng giữ kho) -> deparments.id
            $table->unsignedBigInteger('request_list_id');                 // -> material_request_lists.id
            $table->unsignedBigInteger('request_item_id');                 // -> material_request_items.id
            $table->unsignedBigInteger('category_id');                     // -> material_categories.id
            $table->unsignedBigInteger('import_id');                       // -> material_imports.id
            $table->decimal('amount', 15, 4);
            $table->decimal('issued_amount', 15, 4)->default(0);           // phần đạt đã cấp phát
            $table->string('unit', 50)->nullable();
            $table->string('app_status', 20)->default('pending');          // pending | passed | failed | issued | canceled
            $table->string('request_note', 500)->nullable();
            $table->string('requested_by')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->string('certificate_no', 100)->nullable();             // số giấy chứng nhận / biên bản hiệu chuẩn
            $table->string('result_note', 500)->nullable();
            $table->string('result_by')->nullable();
            $table->dateTime('result_at')->nullable();
            $table->unsignedBigInteger('quarantine_id')->nullable();       // -> material_quarantines.id khi không đạt
            $table->tinyInteger('status_id')->default(1);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index('code', 'material_calibrations_code_index');
            $table->index(['department_id', 'app_status'], 'material_calibrations_dept_status_index');
            $table->index('request_item_id', 'material_calibrations_item_index');
            $table->index('import_id', 'material_calibrations_import_index');
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

        // Quyền xem tab "Hiệu chuẩn" (export_material_tab_calibration) - chỉ tạo quyền tab còn thiếu
        PageTabs::seed();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn('name', array_merge(array_column(self::PERMISSIONS, 1), ['export_material_tab_calibration']))
            ->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('material_calibrations');
    }
};
