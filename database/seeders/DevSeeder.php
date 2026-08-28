<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DỮ LIỆU KHỞI TẠO ĐỂ CHẠY ĐƯỢC APP SAU `migrate:fresh`
 *
 * `migrate:fresh` xoá sạch mọi bảng nên không còn phòng ban / người dùng -> không đăng
 * nhập được. Seeder này tạo lượng dữ liệu tối thiểu:
 *
 *  - 6 phòng ban (QA cố định id = 6 để khớp các *SmokeTest đang hardcode department_id).
 *  - 1 tài khoản quản trị:  Admin / Admin@123  (userGroup = Admin -> toàn quyền).
 *  - 1 chuỗi danh mục hoá chất đã duyệt (nhà SX + tên hoá chất + danh mục + khai cho
 *    phòng QA) để ChemicalEstimateSmokeTest có mặt hàng mà chọn.
 *  - 1 tổ (groups) thuộc phòng QA cho luồng đề nghị vật tư.
 *
 * Chạy lại nhiều lần vẫn an toàn (updateOrInsert theo khoá tự nhiên).
 *
 * Chạy riêng:  php artisan db:seed --class=DevSeeder
 * Hoặc gộp:    php artisan migrate:fresh --seed   (đã khai trong DatabaseSeeder)
 */
class DevSeeder extends Seeder
{
    /** id => [name, shortName]. QA phải là id 6. */
    private const DEPARTMENTS = [
        1 => ['PX Viên 1', 'PXV1'],
        2 => ['PX Viên 2', 'PXV2'],
        3 => ['PX Beta-lactam', 'PXBL'],
        4 => ['Kiểm Nghiệm', 'KN'],
        5 => ['Kho', 'KHO'],
        6 => ['Đảm Bảo Chất Lượng', 'QA'],
    ];

    private const ADMIN_USERNAME = 'Admin';

    private const ADMIN_PASSWORD = 'Admin@123';

    public function run(): void
    {
        $now = now();

        // ---------- Phòng ban ----------
        foreach (self::DEPARTMENTS as $id => [$name, $shortName]) {
            DB::table('deparments')->updateOrInsert(['id' => $id], [
                'name' => $name,
                'shortName' => $shortName,
                'prepareBy' => 'Hệ thống',
                'isActive' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        // ---------- Tài khoản quản trị ----------
        DB::table('user_management')->updateOrInsert(['userName' => self::ADMIN_USERNAME], [
            'passWord' => Hash::make(self::ADMIN_PASSWORD),
            'fullName' => 'Quản trị hệ thống',
            'userGroup' => 'Admin',
            'deparment' => 'QA',
            'isActive' => 1,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        DB::table('roles')->updateOrInsert(['name' => 'Admin'], ['updated_at' => $now, 'created_at' => $now]);

        $adminId = DB::table('user_management')->where('userName', self::ADMIN_USERNAME)->value('id');
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');

        if ($adminId && $adminRoleId) {
            DB::table('user_role')->updateOrInsert(
                ['user_id' => $adminId, 'role_id' => $adminRoleId],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        // ---------- Danh mục hoá chất tối thiểu (đã duyệt) ----------
        DB::table('manufacturers')->updateOrInsert(['short_name' => 'DEMO-NSX'], [
            'name' => 'Nhà sản xuất mẫu',
            'app_status' => 'approved',
            'status_id' => 1,
            'created_by' => 'Hệ thống',
            'approved_by' => 'Hệ thống',
            'approved_at' => $now,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
        $manufacturerId = DB::table('manufacturers')->where('short_name', 'DEMO-NSX')->value('id');

        DB::table('chem_names')->updateOrInsert(['name' => 'Methanol (demo)'], [
            'active_ingredient_name' => 'Methanol',
            'cas_no' => '67-56-1',
            'chemical_formula' => 'CH3OH',
            'app_status' => 'approved',
            'status_id' => 1,
            'created_by' => 'Hệ thống',
            'approved_by' => 'Hệ thống',
            'approved_at' => $now,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
        $chemNameId = DB::table('chem_names')->where('name', 'Methanol (demo)')->value('id');

        $unitId = DB::table('units')->where('short_name', 'kg')->value('id')
            ?: DB::table('units')->where('status_id', 1)->where('app_status', 'approved')->value('id');

        if ($manufacturerId && $chemNameId) {
            DB::table('chemical_categories')->updateOrInsert(['code' => 'H00001'], [
                'type' => 'Dung môi',
                'chem_names_id' => $chemNameId,
                'manufacturers_id' => $manufacturerId,
                'density' => 0.7918,
                'shelf_life_months' => 24,
                'app_status' => 'approved',
                'status_id' => 1,
                'created_by' => 'Hệ thống',
                'approved_by' => 'Hệ thống',
                'approved_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            $categoryId = DB::table('chemical_categories')->where('code', 'H00001')->value('id');

            if ($categoryId) {
                DB::table('department_chemicals')->updateOrInsert(
                    ['department_id' => 6, 'category_id' => $categoryId],
                    [
                        'unit_id' => $unitId,
                        'shelf_life_months' => 24,
                        'status_id' => 1,
                        'created_by' => 'Hệ thống',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        // ---------- Một tổ thuộc phòng QA (luồng đề nghị vật tư) ----------
        DB::table('groups')->updateOrInsert(
            ['name' => 'Tổ mẫu', 'department_id' => 6],
            ['status_id' => 1, 'created_by' => 'Hệ thống', 'updated_at' => $now, 'created_at' => $now]
        );

        $this->command->info('DevSeeder: 6 phòng ban, tài khoản '.self::ADMIN_USERNAME.' / '.self::ADMIN_PASSWORD.', 1 danh mục hoá chất demo (H00001).');
    }
}
