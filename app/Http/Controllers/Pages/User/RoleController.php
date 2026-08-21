<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function index(){
        $datas = DB::table('roles')
            ->leftJoin('role_permission', 'roles.id', '=', 'role_permission.role_id')
            ->leftJoin('permissions', 'role_permission.permission_id', '=', 'permissions.id')
            ->select(
                'roles.id as role_id',
                'roles.name as role_name',
                'permissions.id as permission_id',
                'permissions.display_name as permission_name',
                'permissions.permission_group',
            )
            ->orderBy('role_id')
            ->orderBy('permission_group', 'asc')
            ->get()
            ->groupBy('role_id')
            ->map(function ($items) {
                $permissions = $items->pluck('permission_name', 'permission_id')
                                    ->filter()
                                    ->toArray();

                return [
                    'id' => $items->first()->role_id,
                    'name' => $items->first()->role_name,
                    'permissions' => $permissions
                   
                ];
            })
            ->values();


        session()->put(['title'=> 'DANH SÁCH NHÓM QUYỀN']);
        return view('pages.user.role.list', ['datas' => $datas]);
    }

    public function store_or_update(Request $request){
        try {
            $roleId = $request->input('role_id');
            $permissionId = $request->input('permission_id');
            $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

            if (!$roleId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu role hoặc permission'], 400);
            }

            if ($checked) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            } else {
                if ($roleId != 1) {
                    DB::table('role_permission')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->delete();
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}