<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = DB::table('roles')->orderBy('id', 'asc')->get();

        // Toàn bộ quyền, gom theo nhóm quyền để hiển thị thành từng khối trên bảng
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
            ->get()
            ->groupBy('group_name');

        // Khoá "roleId-permissionId" để view tra nhanh trạng thái checkbox
        $assigned = DB::table('role_permission')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->role_id . '-' . $item->permission_id => true];
            })
            ->toArray();

        session()->put(['title' => 'DANH SÁCH NHÓM QUYỀN']);

        return view('pages.user.role.list', [
            'roles' => $roles,
            'permissions' => $permissions,
            'assigned' => $assigned,
        ]);
    }

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
}
