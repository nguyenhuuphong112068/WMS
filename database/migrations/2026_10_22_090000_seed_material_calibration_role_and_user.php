<?php

use App\Support\PageTabs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Vai trò "Người hiệu chuẩn vật tư": XEM các trang vật tư (kèm mọi tab) + quyền
 * export_material_calibration_confirm - ký xác nhận Đạt / Không đạt cho đề nghị hiệu chuẩn vật tư
 * trước khi cấp phát (App\Support\MaterialCalibration).
 *
 * User 10041 "Nhân viên hiệu chuẩn vật tư" thuộc phòng QA NM2 (Đảm Bảo Chất Lượng, company_id = 2):
 * phòng QA nhận đề nghị hiệu chuẩn của mọi phòng cùng công ty (MaterialCalibration::sourceDepartmentIds).
 * Mật khẩu ban đầu = tên đăng nhập, buộc đổi ở lần đăng nhập đầu (§11.300(b)).
 */
return new class extends Migration
{
    private const ROLE = 'Người hiệu chuẩn vật tư';

    private const ROLE_DESCRIPTION = 'QA hiệu chuẩn vật tư: xem các trang vật tư và ký xác nhận kết quả hiệu chuẩn trước khi cấp phát';

    /** Quyền xem trang vật tư - quyền tab của các trang này (PageTabs) cấp kèm */
    private const VIEW_PERMISSIONS = [
        'materData_material_view',
        'category_material_view',
        'import_material_view',
        'export_material_view',
        'inventory_material_view',
        'estimate_material_view',
    ];

    private const CONFIRM_PERMISSION = 'export_material_calibration_confirm';

    private const USER_NAME = '10041';

    private const FULL_NAME = 'Nhân viên hiệu chuẩn vật tư';

    /** Phòng QA của NM2 */
    private const COMPANY_ID = 2;

    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(['name' => self::ROLE], [
            'description' => self::ROLE_DESCRIPTION,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $roleId = (int) DB::table('roles')->where('name', self::ROLE)->value('id');

        $tabPermissions = [];
        foreach (PageTabs::PAGES as $page => $config) {
            if (in_array($config['parent'], self::VIEW_PERMISSIONS, true)) {
                foreach (array_keys($config['tabs']) as $tab) {
                    $tabPermissions[] = PageTabs::permission($page, $tab);
                }
            }
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_merge(self::VIEW_PERMISSIONS, $tabPermissions, [self::CONFIRM_PERMISSION]))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
        }

        $departmentId = (int) DB::table('deparments')
            ->where('EngshortName', 'QA')
            ->where('company_id', self::COMPANY_ID)
            ->value('id');

        if (! $departmentId || DB::table('user_management')->where('userName', self::USER_NAME)->exists()) {
            return;
        }

        $hash = Hash::make(self::USER_NAME);

        $userId = DB::table('user_management')->insertGetId([
            'userName' => self::USER_NAME,
            'passWord' => $hash,
            'fullName' => self::FULL_NAME,
            'role_id' => $roleId,
            'deparment_id' => $departmentId,
            'company_id' => self::COMPANY_ID,
            'mail' => 'NA',
            'changePWdate' => today()->addDays(90),
            'must_change_password' => true,
            'prepareBy' => 'Claude (setup)',
            'isActive' => 1,
            'failed_login_attempts' => 0,
            'created_at' => $now,
        ]);

        DB::table('password_histories')->insert([
            'user_id' => $userId,
            'password_hash' => $hash,
            'created_by' => 'Claude (setup)',
            'created_at' => $now,
        ]);

        // Vai trò gắn với phòng QA NM2 - chỉ làm việc ở phòng này
        DB::table('user_role')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'department_id' => $departmentId,
        ]);
    }

    public function down(): void
    {
        $userId = DB::table('user_management')->where('userName', self::USER_NAME)->value('id');

        if ($userId) {
            DB::table('user_role')->where('user_id', $userId)->delete();
            DB::table('password_histories')->where('user_id', $userId)->delete();
            DB::table('user_management')->where('id', $userId)->delete();
        }

        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if ($roleId) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('user_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
