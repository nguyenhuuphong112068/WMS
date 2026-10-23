<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nút "Hoàn thành" / "Huỷ" mục dự trù (quyền estimate_*_tracking) là việc của phòng đề nghị:
 * gỡ khỏi vai trò "Nhân Viên Cung Ứng Vật Tư". Vẫn giữ quyền xem tab Theo dõi dự trù (*_tab_tracking).
 */
return new class extends Migration
{
    private const ROLE = 'Nhân Viên Cung Ứng Vật Tư';

    private const PERMISSIONS = ['estimate_material_tracking', 'estimate_chemical_tracking', 'estimate_standard_tracking'];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('role_permission')->where('role_id', $roleId)->whereIn('permission_id', $permissionIds)->delete();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');

        if (! $roleId) {
            return;
        }

        foreach (DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id') as $permissionId) {
            DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
        }
    }
};
