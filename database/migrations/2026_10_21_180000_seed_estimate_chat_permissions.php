<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quyền gửi tin ở cột "Trao đổi" của tab "Theo dõi dự trù" - quyền con của quyền xem tab đó
 * (estimate_<loại>_tab_tracking). Cấp cho mọi nhóm quyền đang xem được tab để không ai mất
 * chức năng đang dùng.
 */
return new class extends Migration
{
    /** quyền mới => [quyền cha, tên hiển thị, mô tả] */
    private const PERMISSIONS = [
        'estimate_material_chat' => ['estimate_material_tab_tracking', 'Trao Đổi Theo Dõi Dự Trù', 'Dự Trù Vật Tư - gửi tin ở cột "Trao đổi" của tab "Theo dõi dự trù"'],
        'estimate_chemical_chat' => ['estimate_chemical_tab_tracking', 'Trao Đổi Theo Dõi Dự Trù', 'Dự Trù Hoá Chất - gửi tin ở cột "Trao đổi" của tab "Theo dõi dự trù"'],
        'estimate_standard_chat' => ['estimate_standard_tab_tracking', 'Trao Đổi Theo Dõi Dự Trù', 'Dự Trù Chất Chuẩn - gửi tin ở cột "Trao đổi" của tab "Theo dõi dự trù"'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $name => [$parentName, $displayName, $description]) {
            $parent = DB::table('permissions')->where('name', $parentName)->first();

            if (! $parent) {
                continue;
            }

            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'permission_group' => $parent->permission_group,
                'parent_id' => $parent->id,
                'display_name' => $displayName,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $permissionId = DB::table('permissions')->where('name', $name)->value('id');

            $roleIds = DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id')
                ->merge(DB::table('roles')->where('name', 'Admin')->pluck('id'))
                ->unique();

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permissionId], []);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');

        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
