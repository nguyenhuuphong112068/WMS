<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PermissionContoller extends Controller
{
    public function index()
    {
        $datas = DB::table('permissions')
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

        session()->put(['title' => 'DANH SÁCH QUYỀN']);

        return view('pages.user.permission.list', ['datas' => $datas]);
    }
}
