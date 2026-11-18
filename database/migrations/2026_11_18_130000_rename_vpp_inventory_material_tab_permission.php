<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 18/11/2026 Tồn Kho VPP không hiện tab "Tồn theo mã VPP": view / PageTabs dùng khoá tab 'material'
 * (quyền inventory_vpp_tab_material) nhưng CSDL chỉ có quyền cũ inventory_vpp_tab_vpp nên @tab ẩn tab
 * với mọi người. Đổi tên quyền cũ sang tên đúng - giữ nguyên id nên các vai trò / người dùng đã được
 * cấp vẫn giữ quyền. Chưa có quyền nào thì tạo mới và cấp cho mọi vai trò đang xem được trang.
 */
return new class extends Migration
{
    private const OLD = 'inventory_vpp_tab_vpp';

    private const NEW = 'inventory_vpp_tab_material';

    public function up(): void
    {
        if (DB::table('permissions')->where('name', self::NEW)->exists()) {
            return;
        }

        $old = DB::table('permissions')->where('name', self::OLD)->first();

        if ($old) {
            DB::table('permissions')->where('id', $old->id)->update([
                'name' => self::NEW,
                'updated_at' => now(),
            ]);

            return;
        }

        $parent = DB::table('permissions')->where('name', 'inventory_vpp_view')->first();

        if (! $parent) {
            return;
        }

        $id = DB::table('permissions')->insertGetId([
            'permission_group' => $parent->permission_group,
            'parent_id' => $parent->id,
            'name' => self::NEW,
            'display_name' => 'Tab Tồn theo mã VPP',
            'description' => 'Xem tab Tồn theo mã VPP: tổng tồn gộp các lô theo VPP, hạn gần nhất, nhà sản xuất; lọc theo phân loại VPP.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleIds = DB::table('role_permission')->where('permission_id', $parent->id)->pluck('role_id')
            ->merge(DB::table('roles')->where('name', 'Admin')->pluck('id'))
            ->unique();

        DB::table('role_permission')->insert($roleIds->map(fn ($roleId) => [
            'role_id' => $roleId,
            'permission_id' => $id,
        ])->values()->all());
    }

    public function down(): void
    {
        if (DB::table('permissions')->where('name', self::OLD)->exists()) {
            return;
        }

        DB::table('permissions')->where('name', self::NEW)->update(['name' => self::OLD, 'updated_at' => now()]);
    }
};
