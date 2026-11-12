<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QUYỀN MODULE VPP - bộ quyền riêng *_vpp_* nhân bản từ quyền Vật Tư cùng chức năng:
 *   category_material_view -> category_vpp_view, export_material_tab_book -> export_vpp_tab_book ...
 *   materData_material_view / _manage -> materData_vpp_view / _manage (nhóm quyền Dữ Liệu Gốc VPP),
 *   materData_material_*_materialSignFlow -> materData_vpp_*_vppSignFlow (Trình Ký Đề Nghị CP VPP),
 *   materData_transferSign_* -> materData_vppTransferSign_* (Trình Ký Liên Phòng Ban VPP).
 * Không nhân bản quyền của dữ liệu gốc dùng chung (Tên Vật Tư, Phân Loại).
 *
 * Quyền mới giữ nguyên cây quyền con (parent_id) và được cấp cho đúng các nhóm quyền / quyền riêng của
 * user đang có quyền Vật Tư tương ứng để không ai mất thao tác đang làm - quản trị bỏ bớt sau.
 *
 * Vật Tư bỏ tab "Đề nghị liên phòng ban - VPP đến HC" (đã sang module VPP) nên xoá quyền
 * export_material_tab_vpp. Nhóm quyền đang phụ trách Vật Tư (roles.kinds) phụ trách thêm VPP.
 */
return new class extends Migration
{
    private const SKIP = '/^materData_material_(review|(view|manage|review)_(materialName|departmentClassification))$/';

    public function up(): void
    {
        $now = now();
        $sources = DB::table('permissions')
            ->where(fn ($q) => $q->where('name', 'like', '%material%')->orWhere('name', 'like', 'materData\_transferSign\_%'))
            ->orderBy('id')
            ->get()
            ->reject(fn ($p) => preg_match(self::SKIP, $p->name));

        $map = [];
        foreach ($sources as $p) {
            $name = $this->vppName($p->name);
            DB::table('permissions')->updateOrInsert(['name' => $name], [
                'permission_group' => $p->permission_group,
                'display_name' => $this->vppLabel($p->display_name, $p->name),
                'description' => $p->description === null ? null : $this->vppLabel($p->description, $p->name),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $map[$p->id] = (int) DB::table('permissions')->where('name', $name)->value('id');
        }

        foreach ($sources as $p) {
            DB::table('permissions')->where('id', $map[$p->id])->update([
                'parent_id' => $p->parent_id ? ($map[$p->parent_id] ?? null) : null,
            ]);

            foreach (DB::table('role_permission')->where('permission_id', $p->id)->pluck('role_id') as $roleId) {
                $row = ['role_id' => $roleId, 'permission_id' => $map[$p->id]];
                if (! DB::table('role_permission')->where($row)->exists()) {
                    DB::table('role_permission')->insert($row);
                }
            }

            foreach (DB::table('user_permission')->where('permission_id', $p->id)->get() as $row) {
                $exists = DB::table('user_permission')->where('user_id', $row->user_id)->where('permission_id', $map[$p->id])->exists();
                if (! $exists) {
                    DB::table('user_permission')->insert(['user_id' => $row->user_id, 'permission_id' => $map[$p->id], 'is_denied' => $row->is_denied]);
                }
            }
        }

        // Tab VPP đã rời Sử Dụng Vật Tư
        $tabId = DB::table('permissions')->where('name', 'export_material_tab_vpp')->value('id');
        if ($tabId) {
            DB::table('role_permission')->where('permission_id', $tabId)->delete();
            DB::table('user_permission')->where('permission_id', $tabId)->delete();
            DB::table('permissions')->where('id', $tabId)->delete();
        }

        foreach (DB::table('roles')->whereNotNull('kinds')->get(['id', 'kinds']) as $role) {
            $kinds = array_filter(explode(',', (string) $role->kinds));
            if (in_array('material', $kinds, true) && ! in_array('vpp', $kinds, true)) {
                array_splice($kinds, array_search('material', $kinds, true) + 1, 0, ['vpp']);
                DB::table('roles')->where('id', $role->id)->update(['kinds' => implode(',', $kinds)]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where(fn ($q) => $q->where('name', 'like', '%\_vpp\_%')->orWhere('name', 'like', '%\_vpp')
                ->orWhere('name', 'like', 'materData\_vppTransferSign\_%'))
            ->where('name', '<>', 'export_material_tab_vpp')
            ->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('user_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        foreach (DB::table('roles')->where('kinds', 'like', '%vpp%')->get(['id', 'kinds']) as $role) {
            $kinds = array_diff(explode(',', (string) $role->kinds), ['vpp']);
            DB::table('roles')->where('id', $role->id)->update(['kinds' => implode(',', $kinds)]);
        }
    }

    private function vppName(string $name): string
    {
        if (str_starts_with($name, 'materData_transferSign_')) {
            return 'materData_vppTransferSign_'.substr($name, strlen('materData_transferSign_'));
        }

        $name = str_replace('materialSignFlow', 'vppSignFlow', $name);

        return preg_replace('/_material(?=_|$)/', '_vpp', $name);
    }

    private function vppLabel(string $text, string $sourceName): string
    {
        if (str_starts_with($sourceName, 'materData_transferSign_')) {
            return preg_replace('/Liên Phòng Ban(?! VPP)/u', 'Liên Phòng Ban VPP', str_replace('vật tư', 'VPP', $text), 1);
        }

        $text = str_replace(['VẬT TƯ', 'Vật Tư', 'Vật tư', 'vật tư'], 'VPP', $text);

        return str_replace('VPP VPP', 'VPP', $text);
    }
};
