<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * 3 model chính của kho + 1 nhóm quyền dùng chung (không gắn model nào).
     * Thứ tự khai báo cũng là thứ tự hiển thị các băng lớn trên bảng.
     */
    private const MODELS = [
        'common'   => ['label' => 'Quyền Chung', 'icon' => 'fa-layer-group'],
        'material' => ['label' => 'Vật Tư', 'icon' => 'fa-box'],
        'chemical' => ['label' => 'Hoá Chất', 'icon' => 'fa-flask'],
        'standard' => ['label' => 'Chất Chuẩn', 'icon' => 'fa-vial'],
    ];

    public function index()
    {
        $roles = DB::table('roles')->orderBy('id', 'asc')->get();

        // Toàn bộ quyền kèm tên nhóm chức năng (menu leftNAV)
        $permissions = DB::table('permissions')
            ->leftJoin('permission_groups', 'permissions.permission_group', '=', 'permission_groups.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.display_name',
                'permissions.description',
                'permission_groups.name as group_name',
                'permission_groups.sort_order',
            )
            ->orderBy('permission_groups.sort_order', 'asc')
            ->orderBy('permissions.id', 'asc')
            ->get();

        // Gom quyền 2 cấp: model chính  ->  nhóm chức năng  ->  danh sách quyền
        $tree = [];
        foreach (self::MODELS as $key => $meta) {
            $tree[$key] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'groups' => []];
        }

        foreach ($permissions as $permission) {
            $modelKey = $this->modelOf($permission->name);
            $groupName = $permission->group_name ?: 'Khác';

            // Nhóm "Dữ Liệu Gốc" tách tiếp theo loại dữ liệu gốc suy từ tên quyền
            if (str_starts_with($permission->name, 'materData_')) {
                $groupName = 'Dữ Liệu Gốc — ' . $this->materDataScopeLabel($permission->name);
            }

            $tree[$modelKey]['groups'][$groupName][] = $permission;
        }

        // Bỏ băng model không có quyền nào để bảng không lòi ra dòng trống
        $tree = array_filter($tree, fn ($model) => !empty($model['groups']));

        // Khoá "roleId-permissionId" để view tra nhanh trạng thái checkbox
        $assigned = DB::table('role_permission')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->role_id . '-' . $item->permission_id => true])
            ->toArray();

        session()->put(['title' => 'DANH SÁCH NHÓM QUYỀN']);

        return view('pages.user.role.list', [
            'roles' => $roles,
            'tree' => $tree,
            'assigned' => $assigned,
        ]);
    }

    /**
     * Suy ra model chính từ tên quyền.
     * Quy ước tên quyền: <khuVực>_<model>_<hànhĐộng> (material / chemical / standard).
     * Nhóm "Đánh Giá Hạn Dùng" (stability_*) chỉ áp dụng cho chất chuẩn.
     */
    private function modelOf(string $name): string
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
    private function materDataScopeLabel(string $name): string
    {
        return match (true) {
            str_contains($name, '_material_') => 'Vật Tư',
            str_contains($name, '_chemical_') => 'Hoá Chất',
            str_contains($name, '_standard_') => 'Chất Chuẩn',
            default => 'Dùng Chung',
        };
    }

    /**
     * Bật / tắt một quyền cho một nhóm quyền (checkbox trên ma trận).
     */
    public function store_or_update(Request $request)
    {
        try {
            $roleId = $request->input('role_id');
            $permissionId = $request->input('permission_id');
            $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

            if (!$roleId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu nhóm quyền hoặc quyền'], 400);
            }

            if ($checked) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            } else {
                // Nhóm quyền Admin (id = 1) luôn giữ toàn quyền, không cho gỡ
                if ($roleId == 1) {
                    return response()->json(['error' => 'Không thể gỡ quyền của nhóm Admin'], 400);
                }

                DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Thêm mới hoặc đổi tên / diễn giải một nhóm quyền.
     * Có 'id' -> cập nhật, không có -> tạo mới.
     */
    public function saveRole(Request $request)
    {
        $id = $request->input('id');

        if ($id && (int) $id === 1) {
            return redirect()->back()
                ->withErrors(['name' => 'Không thể chỉnh sửa nhóm quyền Admin.'], 'roleErrors')
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'string', 'max:100',
                $id ? 'unique:roles,name,' . (int) $id : 'unique:roles,name',
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên nhóm quyền.',
            'name.unique' => 'Tên nhóm quyền này đã tồn tại.',
            'name.max' => 'Tên nhóm quyền không vượt quá :max ký tự.',
            'description.max' => 'Diễn giải không vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'roleErrors')->withInput();
        }

        if ($id) {
            $old = DB::table('roles')->where('id', $id)->first();

            DB::table('roles')->where('id', $id)->update([
                'name' => $request->name,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            AuditTrialController::log(
                'Cập nhật', 'roles', $id,
                $old->name ?? '',
                'Sửa nhóm quyền: ' . $request->name
            );

            return redirect()->back()->with('success', 'Đã cập nhật nhóm quyền!');
        }

        $newId = DB::table('roles')->insertGetId([
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Thêm mới', 'roles', $newId, 'NA', 'Tạo nhóm quyền: ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm nhóm quyền!');
    }

    /**
     * Xoá một nhóm quyền chưa được gán cho người dùng nào.
     */
    public function deleteRole($id)
    {
        $id = (int) $id;

        if ($id === 1) {
            return redirect()->back()->withErrors(['name' => 'Không thể xoá nhóm quyền Admin.'], 'roleErrors');
        }

        $role = DB::table('roles')->where('id', $id)->first();

        if (!$role) {
            return redirect()->back()->withErrors(['name' => 'Nhóm quyền không tồn tại.'], 'roleErrors');
        }

        $inUse = DB::table('user_role')->where('role_id', $id)->exists()
            || DB::table('user_management')->where('role_id', $id)->exists();

        if ($inUse) {
            return redirect()->back()->withErrors(
                ['name' => 'Nhóm quyền "' . $role->name . '" đang được gán cho người dùng, không thể xoá.'],
                'roleErrors'
            );
        }

        DB::table('role_permission')->where('role_id', $id)->delete();
        DB::table('roles')->where('id', $id)->delete();

        AuditTrialController::log('Xoá', 'roles', $id, $role->name, 'Xoá nhóm quyền');

        return redirect()->back()->with('success', 'Đã xoá nhóm quyền!');
    }
}
