<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwitchProductionController extends Controller
{
    public function switchProduction(Request $request)
    {
        // Lấy thông tin phòng ban từ shortName
        $selected_dept_name = $request->selected_department;
        $user = $request->session()->get('user', []);
        $selected_department_id = DB::table('deparments')->where('shortName', $selected_dept_name)->value('id');

        $request->session()->put('user', [
            'userId'          => $user['userId'] ?? null,
            'userName'        => $user['userName'] ?? null,
            'fullName'        => $user['fullName'] ?? null,
            'userGroup'       => $user['userGroup'] ?? null,
            'department'      => $user['department'] ?? null,
            'department_id'   => $user['department_id'] ?? null,
            'passWord'        => $user['passWord'] ?? null,
            'selected_department' => $selected_dept_name,
            'selected_department_id' => $selected_department_id
        ]);

        session()->put(['title' => 'KẾ HOẠCH SẢN XUẤT']);

        // Nếu có redirect URL thì quay lại đó
        if ($request->has('redirect')) {
            return redirect($request->redirect);
        }
        return view('pages.home');
    }
}
