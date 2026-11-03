<?php

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rà bảng permissions (03/11/2026): bỏ các quyền không còn chỗ nào trong code kiểm tra (đã đối chiếu cả tên ghép động
 * của PageTabs, MaterDataPermissions, $permPrefix danh mục) và không có trong Hướng Dẫn Sử Dụng.
 *  - export_chemical_approve        : luồng duyệt cấp phát hoá chất không còn
 *  - inventory_export_excel         : Tồn Kho không có nút xuất Excel kiểm theo quyền này
 *  - stability_plan_create / update : tab Kế hoạch đánh giá chỉ xem (stability_plan_view)
 *  - import_chemical_receive / rejectTransfer : nhận / từ chối chuyển hoá chất nay dùng export_chemical_transfer_receive / _return
 *  - is_BOD                         : tab "Ký duyệt (mọi phòng ban)" ở Sử Dụng Vật Tư đã bỏ (ký từ Trang chủ)
 * Đổi tên hiển thị export_chemical_request cho đúng nút nó mở (Tạo đề nghị chuyển liên phòng ban).
 */
return new class extends Migration
{
    private const UNUSED = [
        'export_chemical_approve',
        'inventory_export_excel',
        'stability_plan_create',
        'stability_plan_update',
        'import_chemical_receive',
        'import_chemical_rejectTransfer',
        'is_BOD',
    ];

    private const ACTOR = 'Rà soát quyền không dùng (system)';

    public function up(): void
    {
        foreach (DB::table('permissions')->whereIn('name', self::UNUSED)->get() as $permission) {
            $roles = DB::table('role_permission')->join('roles', 'roles.id', '=', 'role_permission.role_id')
                ->where('permission_id', $permission->id)->pluck('roles.name')->implode(', ');

            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            DB::table('user_permission')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('parent_id', $permission->id)->update(['parent_id' => null]);
            DB::table('permissions')->where('id', $permission->id)->delete();

            AuditTrialController::log('Xoá', 'permissions', $permission->id,
                $permission->name . ' (' . $permission->display_name . ') - nhóm quyền: ' . ($roles ?: 'không'),
                'Bỏ quyền không còn dùng', self::ACTOR);
        }

        DB::table('permissions')->where('name', 'export_chemical_request')->update([
            'display_name' => 'Tạo Đề Nghị Chuyển Hoá Chất Liên Phòng Ban',
            'description' => 'Tạo / sửa phiếu nháp đề nghị chuyển hoá chất liên phòng ban',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Quyền đã bỏ không còn chỗ nào dùng; danh sách nhóm quyền có quyền đó lưu ở Audit Trail
    }
};
