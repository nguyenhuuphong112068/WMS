<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SwitchProductionController extends Controller
{
    public function switchProduction(Request $request)
    {
        // Chọn công ty trước rồi tới phòng ban nên shortName có thể trùng giữa hai công ty:
        // ưu tiên id phòng ban gửi kèm, không có mới dò theo shortName.
        $selected_dept_name = $request->selected_department;
        $user = $request->session()->get('user', []);

        $selected_dept = DB::table('deparments')
            ->when(
                $request->filled('selected_department_id'),
                fn ($q) => $q->where('id', $request->selected_department_id),
                fn ($q) => $q->where('shortName', $selected_dept_name)
            )
            ->where('isActive', 1)
            ->select('id', 'shortName')
            ->first();

        // Mọi user chuyển được sang mọi phòng ban; phòng ngoài phạm vi làm việc chỉ được xem
        // (user_department_view_only + middleware ViewOnlyDepartment)
        if (! $selected_dept) {
            return redirect()->back()->with('error', 'Phòng ban không tồn tại hoặc đã ngưng hoạt động.');
        }

        $selected_department_id = $selected_dept->id;
        $selected_dept_name = $selected_dept->shortName;

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
