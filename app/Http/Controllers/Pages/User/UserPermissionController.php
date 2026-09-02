<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cấp quyền riêng cho từng user, ngoài quyền user nhận được từ nhóm quyền (role).
 * Mỗi quyền có 3 trạng thái:
 *  - inherit : theo nhóm quyền
 *  - allow   : cấp riêng cho user này (dù nhóm quyền không có)
 *  - deny    : chặn riêng user này (dù nhóm quyền có)
 */
class UserPermissionController extends Controller
{
    public function index(string|int $userId)
    {
        $user = DB::table('user_management')->where('id', $userId)->first();

        if (!$user) {
            return response()->json(['error' => 'Không tìm thấy người dùng'], 404);
        }

        // Quyền user đang có thông qua nhóm quyền
        $fromRole = DB::table('role_permission')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->pluck('role_permission.permission_id')
            ->all();
        $fromRole = array_flip($fromRole);

        // Quyền đã cấp riêng cho user
        $overrides = DB::table('user_permission')
            ->where('user_id', $userId)
            ->pluck('is_denied', 'permission_id')
            ->all();

        $datas = DB::table('permissions')
            ->leftJoin('permission_groups', 'permissions.permission_group', '=', 'permission_groups.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.display_name',
                'permission_groups.name as group_name',
                'permission_groups.sort_order',
            )
            ->orderBy('permission_groups.sort_order', 'asc')
            ->orderBy('permissions.id', 'asc')
            ->get()
            ->map(function ($permission) use ($fromRole, $overrides) {
                if (array_key_exists($permission->id, $overrides)) {
                    $state = $overrides[$permission->id] ? 'deny' : 'allow';
                } else {
                    $state = 'inherit';
                }

                return [
                    'id' => $permission->id,
                    'name' => $permission->display_name ?: $permission->name,
                    'code' => $permission->name,
                    'group_name' => $permission->group_name ?: 'Khác',
                    'from_role' => isset($fromRole[$permission->id]),
                    'state' => $state,
                ];
            })
            ->values();

        return response()->json([
            'user' => ['id' => $user->id, 'fullName' => $user->fullName],
            'datas' => $datas,
        ]);
    }

    public function store_or_update(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $permissionId = $request->input('permission_id');
            $state = $request->input('state'); // inherit | allow | deny

            if (!$userId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu người dùng hoặc quyền'], 400);
            }

            if (!in_array($state, ['inherit', 'allow', 'deny'])) {
                return response()->json(['error' => 'Trạng thái quyền không hợp lệ'], 400);
            }

            // Không cho chặn quyền của tài khoản thuộc nhóm quyền Admin
            if ($state == 'deny') {
                $isAdmin = DB::table('user_role')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->where('user_role.user_id', $userId)
                    ->where('roles.name', 'Admin')
                    ->exists();

                if ($isAdmin) {
                    return response()->json(['error' => 'Không thể chặn quyền của tài khoản Admin'], 400);
                }
            }

            if ($state == 'inherit') {
                DB::table('user_permission')
                    ->where('user_id', $userId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            } else {
                DB::table('user_permission')->updateOrInsert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ], [
                    'is_denied' => $state == 'deny',
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
