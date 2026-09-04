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

        // Đổi phòng ban là đổi luôn công ty đang làm việc (mỗi phòng ban thuộc một công ty)
        $companyId = \App\Support\CompanyContext::resolveForDepartment(
            $selected_department_id ? (int) $selected_department_id : null
        );

        $request->session()->put('user', [
            'userId'          => $user['userId'] ?? null,
            'userName'        => $user['userName'] ?? null,
            'fullName'        => $user['fullName'] ?? null,
            'userGroup'       => $user['userGroup'] ?? null,
            'department'      => $user['department'] ?? null,
            'department_id'   => $user['department_id'] ?? null,
            // KHÔNG lưu mật khẩu vào session (§11.300) - cần thì đọc lại từ DB.
            'selected_department' => $selected_dept_name,
            'selected_department_id' => $selected_department_id,
            'company_id'      => $companyId,
            'company_name'    => \App\Support\CompanyContext::name($companyId),
            'last_activity_ts' => $user['last_activity_ts'] ?? now()->timestamp,
        ]);

        session()->put(['title' => 'KẾ HOẠCH SẢN XUẤT']);

        // Nếu có redirect URL thì quay lại đó
        if ($request->has('redirect')) {
            return redirect($request->redirect);
        }
        return view('pages.home');
    }
}
