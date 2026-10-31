<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ma trận nhóm quyền × quyền, dùng chung cho màn hình Phân Quyền › Nhóm Quyền và tài liệu
 * Hướng Dẫn Sử Dụng (bảng phân quyền chung + bảng "vai trò được làm gì" của từng module).
 *
 * Quy tắc hiệu lực khớp user_permission_names():
 *  - nhóm quyền Admin luôn có toàn bộ quyền;
 *  - quyền con (permissions.parent_id) chỉ có hiệu lực khi nhóm quyền có cả quyền cha.
 */
class PermissionMatrix
{
    /**
     * 3 model chính của kho + 1 nhóm quyền dùng chung (không gắn model nào).
     * Thứ tự khai báo cũng là thứ tự hiển thị các băng lớn trên bảng.
     */
    public const MODELS = [
        'common'   => ['label' => 'Quyền Chung', 'icon' => 'fa-layer-group'],
        'material' => ['label' => 'Vật Tư', 'icon' => 'fa-box'],
        'chemical' => ['label' => 'Hoá Chất', 'icon' => 'fa-flask'],
        'standard' => ['label' => 'Chất Chuẩn', 'icon' => 'fa-vial'],
    ];

    /** Quyền nằm ở băng "Quyền Chung" nhưng chỉ dùng cho nghiệp vụ của một model */
    private const EXTRA_BY_MODEL = [
        'material' => ['is_BOD'],
    ];

    /**
     * Toàn bộ ma trận: danh sách nhóm quyền, cây quyền 2 cấp (model -> nhóm chức năng -> quyền)
     * và khoá "roleId-permissionId" của các ô đã tick.
     */
    public static function build(): array
    {
        $roles = DB::table('roles')->orderBy('id', 'asc')->get();
        $permissions = self::permissions();

        // Gom quyền 2 cấp: model chính  ->  nhóm chức năng  ->  danh sách quyền
        $tree = [];
        foreach (self::MODELS as $key => $meta) {
            $tree[$key] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'groups' => []];
        }

        foreach ($permissions as $permission) {
            $modelKey = self::modelOf($permission->name);
            $groupName = $permission->group_name ?: 'Khác';

            // Nhóm "Dữ Liệu Gốc" tách tiếp theo loại dữ liệu gốc suy từ tên quyền
            if (str_starts_with($permission->name, 'materData_')) {
                $groupName = 'Dữ Liệu Gốc — ' . self::materDataScopeLabel($permission->name);
            }

            $tree[$modelKey]['groups'][$groupName][] = $permission;
        }

        // Quyền con (parent_id) đứng ngay dưới quyền cha trong cùng nhóm
        foreach ($tree as &$model) {
            foreach ($model['groups'] as &$groupPermissions) {
                $groupPermissions = self::nestChildren($groupPermissions);
            }
        }
        unset($model, $groupPermissions);

        // Bỏ băng model không có quyền nào để bảng không lòi ra dòng trống
        $tree = array_filter($tree, fn ($model) => !empty($model['groups']));

        return [
            'roles' => $roles,
            'tree' => $tree,
            'assigned' => self::assigned(),
        ];
    }

    /**
     * "Vai trò được làm gì" trong một phạm vi, chỉ tính quyền có hiệu lực.
     * $scope: material | chemical | standard | common, hoặc materData (riêng nhóm Dữ Liệu Gốc).
     *
     * Trả về [roleId => [
     *     'role'    => bản ghi roles,
     *     'isAdmin' => true khi là nhóm Admin (toàn quyền, không liệt kê),
     *     'onlyView'=> mọi quyền thao tác đều là quyền Xem,
     *     'groups'  => [tên nhóm chức năng => ['actions' => [nhãn], 'tabs' => [nhãn]]],
     * ]], bỏ các nhóm quyền không có quyền nào trong phạm vi.
     */
    public static function roleSummary(string $scope): array
    {
        $permissions = self::permissions()->filter(fn ($permission) => self::inScope($permission->name, $scope));
        $assigned = self::assigned();
        $parentOf = $permissions->pluck('parent_id', 'id')->all();
        $summary = [];

        foreach (DB::table('roles')->orderBy('id', 'asc')->get() as $role) {
            if ($role->name === 'Admin') {
                $summary[$role->id] = ['role' => $role, 'isAdmin' => true, 'onlyView' => false, 'groups' => []];
                continue;
            }

            $groups = [];
            $onlyView = true;

            foreach ($permissions as $permission) {
                if (!self::isEffective($role->id, $permission->id, $parentOf, $assigned)) {
                    continue;
                }

                $groupName = $scope === 'materData'
                    ? self::materDataScopeLabel($permission->name)
                    : ($permission->group_name ?: 'Khác');
                $groups[$groupName] ??= ['actions' => [], 'tabs' => []];

                $label = $permission->display_name ?: $permission->name;
                if (str_contains($permission->name, '_tab_')) {
                    $groups[$groupName]['tabs'][] = preg_replace('/^Tab\s+/u', '', $label);
                    continue;
                }

                $groups[$groupName]['actions'][] = self::shortLabel($label);
                if (!str_ends_with($permission->name, '_view')) {
                    $onlyView = false;
                }
            }

            if ($groups) {
                $summary[$role->id] = ['role' => $role, 'isAdmin' => false, 'onlyView' => $onlyView, 'groups' => $groups];
            }
        }

        return $summary;
    }

    /**
     * Suy ra model chính từ tên quyền.
     * Quy ước tên quyền: <khuVực>_<model>_<hànhĐộng> (material / chemical / standard).
     * Nhóm "Đánh Giá Hạn Dùng" (stability_*) chỉ áp dụng cho chất chuẩn.
     */
    public static function modelOf(string $name): string
    {
        // Toàn bộ quyền "Dữ Liệu Gốc" nằm ở card "Quyền Chung", chia tiếp theo loại ở cấp nhóm
        if (str_starts_with($name, 'materData_')) {
            return 'common';
        }
        if (str_contains($name, '_material_') || str_starts_with($name, 'material_')) {
            return 'material';
        }
        if (str_contains($name, '_chemical_') || str_starts_with($name, 'chemical_')) {
            return 'chemical';
        }
        if (str_contains($name, '_standard_') || str_starts_with($name, 'standard_') || str_starts_with($name, 'stability_')) {
            return 'standard';
        }

        return 'common';
    }

    /**
     * Nhãn loại dữ liệu gốc, suy từ tên quyền materData_<scope>_<action>.
     */
    public static function materDataScopeLabel(string $name): string
    {
        return match (true) {
            // Màn Trình Ký Đề Nghị Liên Phòng Ban có bộ quyền riêng (materData_transferSign_*)
            str_contains($name, '_transferSign_') => 'Trình Ký Liên Phòng Ban',
            str_contains($name, '_material_') => 'Vật Tư',
            str_contains($name, '_chemical_') => 'Hoá Chất',
            str_contains($name, '_standard_') => 'Chất Chuẩn',
            default => 'Dùng Chung',
        };
    }

    /**
     * Xếp quyền con ngay sau quyền cha (giữ thứ tự gốc của quyền cha), quyền con có cha
     * nằm nhóm khác / không tồn tại thì giữ nguyên chỗ.
     */
    public static function nestChildren(array $permissions): array
    {
        $ids = array_map(fn ($permission) => $permission->id, $permissions);
        $children = [];

        foreach ($permissions as $permission) {
            if ($permission->parent_id && in_array($permission->parent_id, $ids)) {
                $children[$permission->parent_id][] = $permission;
            }
        }

        // Đệ quy để quyền nhiều tầng (vd. Trao đổi -> tab Theo dõi -> Xem trang) cũng hiện, depth để thụt lề
        $ordered = [];
        $append = function ($permission, int $depth) use (&$append, &$ordered, $children) {
            $permission->depth = $depth;
            $ordered[] = $permission;
            foreach ($children[$permission->id] ?? [] as $child) {
                $append($child, $depth + 1);
            }
        };

        foreach ($permissions as $permission) {
            if ($permission->parent_id && in_array($permission->parent_id, $ids)) {
                continue;
            }

            $append($permission, 0);
        }

        return $ordered;
    }

    /**
     * Ô đã tick nhưng chưa có hiệu lực: quyền con mà nhóm quyền thiếu quyền cha (ở bất kỳ tầng nào).
     */
    public static function isEffective(int $roleId, int $permissionId, array $parentOf, array $assigned): bool
    {
        $id = $permissionId;
        while ($id) {
            if (!isset($assigned[$roleId . '-' . $id])) {
                return false;
            }
            $id = $parentOf[$id] ?? null;
        }

        return true;
    }

    /** Toàn bộ quyền kèm tên nhóm chức năng (menu leftNAV) */
    private static function permissions()
    {
        return DB::table('permissions')
            ->leftJoin('permission_groups', 'permissions.permission_group', '=', 'permission_groups.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.display_name',
                'permissions.description',
                Schema::hasColumn('permissions', 'parent_id') ? 'permissions.parent_id' : DB::raw('NULL as parent_id'),
                'permission_groups.name as group_name',
                'permission_groups.sort_order',
            )
            ->orderBy('permission_groups.sort_order', 'asc')
            ->orderBy('permissions.id', 'asc')
            ->get();
    }

    /** Khoá "roleId-permissionId" để tra nhanh trạng thái checkbox */
    private static function assigned(): array
    {
        return DB::table('role_permission')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->role_id . '-' . $item->permission_id => true])
            ->toArray();
    }

    private static function inScope(string $name, string $scope): bool
    {
        if ($scope === 'materData') {
            return str_starts_with($name, 'materData_');
        }

        return self::modelOf($name) === $scope || in_array($name, self::EXTRA_BY_MODEL[$scope] ?? [], true);
    }

    /**
     * Bỏ đuôi tên model / phạm vi dữ liệu gốc ở cuối nhãn vì bảng đã nằm trong tài liệu của model đó:
     * "Thêm Phiếu Nhập Hoá Chất" -> "Thêm Phiếu Nhập", "Sửa Dữ Liệu Gốc Vật Tư" -> "Sửa".
     */
    private static function shortLabel(string $label): string
    {
        $short = preg_replace(
            '/\s+(Dữ Liệu Gốc (Vật Tư|Hoá Chất|Chất Chuẩn|Dùng Chung)|Liên Phòng Ban|Vật Tư Chung|Vật Tư|Hoá Chất|Hóa Chất|Chất Chuẩn Chung|Chất Chuẩn)$/u',
            '',
            $label
        );

        return $short !== '' ? $short : $label;
    }
}
