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

    /**
     * Card trên màn Phân Quyền › Nhóm Quyền: theo module của leftNAV ($navModules), cùng thứ tự.
     * 'groups' = các permission_groups.name thuộc module.
     */
    public const MODULES = [
        'materData' => ['label' => 'Dữ Liệu Gốc', 'icon' => 'fa-database', 'groups' => ['Dữ Liệu Gốc']],
        'category'  => ['label' => 'Danh Mục', 'icon' => 'fa-clipboard-list', 'groups' => ['Danh Mục']],
        'import'    => ['label' => 'Nhập', 'icon' => 'fa-dolly', 'groups' => ['Nhập']],
        'export'    => ['label' => 'Đề Nghị - Sử Dụng', 'icon' => 'fa-hand-holding-water', 'groups' => ['Sử Dụng']],
        'inventory' => ['label' => 'Tồn Kho', 'icon' => 'fa-boxes', 'groups' => ['Tồn Kho', 'Đánh Giá Hạn Dùng']],
        'estimate'  => ['label' => 'Dự Trù', 'icon' => 'fa-file-signature', 'groups' => ['Dự Trù']],
        'admin'     => ['label' => 'Phân Quyền & Lịch Sử', 'icon' => 'fa-user-shield', 'groups' => ['Quản Trị']],
        'other'     => ['label' => 'Khác', 'icon' => 'fa-ellipsis-h', 'groups' => []],
    ];

    /** Loại hàng nhóm quyền phụ trách, chọn khi tạo / sửa nhóm quyền (roles.kinds) */
    public const ROLE_KINDS = ['material' => 'Vật Tư', 'chemical' => 'Hoá Chất', 'standard' => 'Chất Chuẩn'];

    /** Loại hàng của dòng nhóm con trong card module, dùng cho bộ lọc Vật Tư / Hoá Chất / Chất Chuẩn */
    public const KINDS = [
        'material' => ['label' => 'Vật Tư', 'icon' => 'fa-box'],
        'chemical' => ['label' => 'Hoá Chất', 'icon' => 'fa-flask'],
        'standard' => ['label' => 'Chất Chuẩn', 'icon' => 'fa-vial'],
        'common'   => ['label' => 'Dùng Chung', 'icon' => 'fa-layer-group'],
    ];

    /** Nhóm quyền luôn xếp ở loại Chung trên bảng phân quyền, không suy theo quyền đang có */
    private const COMMON_ROLES = ['Admin', 'Ban Giám Đốc'];

    /** Thứ tự dòng nhóm con trong một card module */
    private const SUB_GROUP_ORDER = [
        'Vật Tư', 'Hoá Chất', 'Chất Chuẩn', 'Dùng Chung',
        'Trình Ký Liên Phòng Ban', 'Khởi Tạo Dữ Liệu', 'Phân Quyền', 'Lịch Sử & Audit Trail',
    ];

    /**
     * Toàn bộ ma trận: danh sách nhóm quyền, cây quyền 2 cấp (model -> nhóm chức năng -> quyền)
     * và khoá "roleId-permissionId" của các ô đã tick.
     */
    public static function build(): array
    {
        $permissions = self::permissions();
        $roles = self::sortRoles(DB::table('roles')->orderBy('id', 'asc')->get(), $permissions);

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
            'modules' => self::modules($permissions),
            'kinds' => self::KINDS,
            'roleKinds' => self::ROLE_KINDS,
            // Dòng nhóm con nào không phải Vật Tư / Hoá Chất / Chất Chuẩn thì tính là Dùng Chung
            'kindOfGroup' => array_flip(array_map(fn ($kind) => $kind['label'], array_slice(self::KINDS, 0, 3))),
            'assigned' => self::assigned(),
        ];
    }

    /**
     * Cây quyền theo module leftNAV: module -> Vật Tư / Hoá Chất / Chất Chuẩn / Dùng Chung -> quyền.
     * Dữ Liệu Gốc chia theo loại dữ liệu gốc, Quản Trị chia Phân Quyền / Lịch Sử.
     */
    private static function modules($permissions): array
    {
        $moduleOfGroup = [];
        $modules = [];
        foreach (self::MODULES as $key => $meta) {
            foreach ($meta['groups'] as $groupName) {
                $moduleOfGroup[$groupName] = $key;
            }
            $modules[$key] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'groups' => []];
        }

        $modelLabels = ['material' => 'Vật Tư', 'chemical' => 'Hoá Chất', 'standard' => 'Chất Chuẩn', 'common' => 'Dùng Chung'];

        foreach ($permissions as $permission) {
            $moduleKey = $moduleOfGroup[$permission->group_name] ?? 'other';

            $subGroup = match ($moduleKey) {
                'materData' => self::materDataScopeLabel($permission->name),
                'admin' => preg_match('/^(auditTrail_|login_history_)/', $permission->name) ? 'Lịch Sử & Audit Trail' : 'Phân Quyền',
                default => $modelLabels[self::modelOf($permission->name)],
            };

            $modules[$moduleKey]['groups'][$subGroup][] = $permission;
        }

        foreach ($modules as &$module) {
            uksort($module['groups'], fn ($a, $b) => self::subGroupRank($a) <=> self::subGroupRank($b));
            foreach ($module['groups'] as &$groupPermissions) {
                $groupPermissions = self::nestChildren($groupPermissions);
            }
        }
        unset($module, $groupPermissions);

        return array_filter($modules, fn ($module) => !empty($module['groups']));
    }

    /**
     * Xếp cột nhóm quyền theo loại Chung -> Vật Tư -> Hoá Chất -> Chất Chuẩn, trong cùng loại nhóm nhiều quyền hơn đứng trước.
     * Gắn thêm $role->kinds (loại phụ trách), $role->kind (cột xếp vào) và $role->permission_count.
     * Loại lấy theo roles.kinds chọn khi tạo nhóm quyền; chưa chọn thì suy tự động (roleKind).
     */
    private static function sortRoles($roles, $permissions)
    {
        $business = array_keys(self::ROLE_KINDS);
        $kindOf = $permissions->mapWithKeys(fn ($permission) => [$permission->id => self::kindOf($permission->name)])->all();
        $counts = [];
        foreach (DB::table('role_permission')->get() as $item) {
            if (isset($kindOf[$item->permission_id])) {
                $counts[$item->role_id][$kindOf[$item->permission_id]] = ($counts[$item->role_id][$kindOf[$item->permission_id]] ?? 0) + 1;
            }
        }

        $kindOrder = array_flip(['common', 'material', 'chemical', 'standard']);

        return $roles->map(function ($role) use ($counts, $business) {
            $chosen = in_array($role->name, self::COMMON_ROLES, true)
                ? $business
                : array_values(array_intersect($business, explode(',', $role->kinds ?? '')));

            if ($chosen) {
                $role->kinds = $chosen;
                $role->kind = count($chosen) === 1 ? $chosen[0] : 'common';
            } else {
                $role->kind = self::roleKind($role, $counts[$role->id] ?? []);
                $role->kinds = $role->kind === 'common' ? $business : [$role->kind];
            }
            $role->permission_count = array_sum($counts[$role->id] ?? []);

            return $role;
        })->sort(fn ($a, $b) => [$kindOrder[$a->kind], $b->permission_count, $a->id]
            <=> [$kindOrder[$b->kind], $a->permission_count, $b->id])->values();
    }

    /**
     * Loại của nhóm quyền: >= 80% quyền nghiệp vụ (Vật Tư / Hoá Chất / Chất Chuẩn) rơi vào một loại thì là loại đó,
     * trải nhiều loại là Chung; chưa có quyền nghiệp vụ nào thì suy theo tên nhóm.
     */
    private static function roleKind($role, array $counts): string
    {
        if (in_array($role->name, self::COMMON_ROLES, true)) {
            return 'common';
        }

        $business = array_intersect_key($counts, array_flip(['material', 'chemical', 'standard']));
        $total = array_sum($business);
        arsort($business);

        // Quyền trải nhiều loại -> Chung (vd. Nhân Viên Cung Ứng Vật Tư)
        if ($total > 0 && reset($business) / $total < 0.8) {
            return 'common';
        }

        // Tên nói rõ loại thì theo tên, tránh nhóm mới tick vài quyền lệch loại bị xếp sai
        return match (true) {
            (bool) preg_match('/vật tư/iu', $role->name) => 'material',
            (bool) preg_match('/hoá chất|hóa chất/iu', $role->name) => 'chemical',
            (bool) preg_match('/chuẩn/iu', $role->name) => 'standard',
            $total > 0 => key($business),
            default => 'common',
        };
    }

    /** Loại hàng của một quyền; quyền Dữ Liệu Gốc suy theo loại dữ liệu gốc */
    private static function kindOf(string $name): string
    {
        if (str_starts_with($name, 'materData_')) {
            return match (self::materDataScopeLabel($name)) {
                'Vật Tư' => 'material',
                'Hoá Chất' => 'chemical',
                'Chất Chuẩn' => 'standard',
                default => 'common',
            };
        }

        return self::modelOf($name);
    }

    private static function subGroupRank(string $name): int
    {
        $rank = array_search($name, self::SUB_GROUP_ORDER, true);

        return $rank === false ? 99 : $rank;
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

                // Dữ Liệu Gốc: gom theo từng màn hình (quyền con MaterDataPermissions), bỏ quyền nhóm phủ nhiều màn
                if ($scope === 'materData' && ($screen = MaterDataPermissions::parse($permission->name))) {
                    [$screenKey, $action] = $screen;
                    $groupName = self::materDataScopeLabel($permission->name) . ' › ' . MaterDataPermissions::SCREENS[$screenKey][1];
                    $groups[$groupName] ??= ['actions' => [], 'tabs' => []];
                    $groups[$groupName]['actions'][] = MaterDataPermissions::ACTIONS[$action];
                    if ($action !== 'view') {
                        $onlyView = false;
                    }
                    continue;
                }
                if ($scope === 'materData' && MaterDataPermissions::isGroupPermission($permission->name)) {
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
            str_contains($name, '_initialData_') => 'Khởi Tạo Dữ Liệu',
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

        return self::modelOf($name) === $scope;
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
