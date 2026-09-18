<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo 1 tài khoản test cho mỗi phòng ban đang hoạt động, gán role nghiệp vụ
 * (không tính Admin) xoay vòng qua từng phòng để có dữ liệu test đa dạng vai trò.
 *
 * Bỏ qua phòng ban đã có user (userName trùng) nên chạy lại nhiều lần vẫn an toàn,
 * không ghi đè mật khẩu của tài khoản đã tồn tại.
 *
 * Chạy riêng: php artisan db:seed --class=DepartmentUserSeeder
 */
class DepartmentUserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Wms@2026';

    public function run(): void
    {
        $now = now();

        $roles = DB::table('roles')
            ->where('name', '!=', 'Admin')
            ->orderBy('id')
            ->pluck('name', 'id');

        if ($roles->isEmpty()) {
            $this->command->warn('DepartmentUserSeeder: chưa có role nghiệp vụ nào, bỏ qua.');
            return;
        }

        $roleIds = $roles->keys()->values();
        $roleCount = $roleIds->count();

        $departments = DB::table('deparments')
            ->where('isActive', 1)
            ->orderBy('id')
            ->get(['id', 'name', 'company_id']);

        $created = 0;

        foreach ($departments as $index => $department) {
            $userName = 'user'.str_pad((string) $department->id, 2, '0', STR_PAD_LEFT);

            if (DB::table('user_management')->where('userName', $userName)->exists()) {
                continue;
            }

            $roleId = $roleIds[$index % $roleCount];
            $roleName = $roles[$roleId];
            $pwHash = Hash::make(self::DEFAULT_PASSWORD);

            $userId = DB::table('user_management')->insertGetId([
                'userName' => $userName,
                'passWord' => $pwHash,
                'fullName' => $roleName.' - '.$department->name,
                'role_id' => $roleId,
                'deparment_id' => $department->id,
                'company_id' => $department->company_id,
                'mail' => 'NA',
                'changePWdate' => today()->addDays(90),
                'must_change_password' => true,
                'isActive' => 1,
                'prepareBy' => 'Hệ thống',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('user_role')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('password_histories')->insert([
                'user_id' => $userId,
                'password_hash' => $pwHash,
                'created_by' => 'Hệ thống',
                'created_at' => $now,
            ]);

            $created++;
        }

        $this->command->info("DepartmentUserSeeder: đã tạo {$created} user mới (mật khẩu mặc định ".self::DEFAULT_PASSWORD.'), bỏ qua phòng ban đã có user.');
    }
}
