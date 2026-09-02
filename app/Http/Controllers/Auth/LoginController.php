<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // dd ($request->all());

    public function showLogin()
    {

        session()->put(['title' => 'WMS - QUẢN LÝ KHO']);

        return view('login', []);
    }

    public function login(Request $request)
    {

        // $hash = Hash::make("Abc@123"); //  password_hash("Abc@123", PASSWORD_DEFAULT);

        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();

        if (is_null($getUser)) {
            return redirect()->route('login')->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }

        if (! Hash::check($request->passWord, $getUser->passWord)) {

            return redirect()->route('login')->with('error', 'PassWord Không Chính Xác, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }

        $request->session()->put('user', [
            'userId' => $getUser->id,
            'userName' => $getUser->userName,
            'fullName' => $getUser->fullName,
            'passWord' => $request->passWord,
            'userGroup' => $getUser->userGroup,
            'department' => $getUser->deparment,
            'department_id' => DB::table('deparments')->where('shortName', $getUser->deparment)->first()->id,
            'selected_department' => $getUser->deparment,
            'selected_department_id' => DB::table('deparments')->where('shortName', $getUser->deparment)->first()->id,

        ]);

        AuditTrialController::log('Login', 'NA', 0, 'NA', 'Đăng Nhập Thành Công');

        return redirect()->route('pages.home');
    }

    public function logout(Request $request)
    {
        AuditTrialController::log('Log Out', 'NA', 0, 'NA', 'Đăng Xuất');
        $request->session()->flush();

        return redirect()->route('login');
    }

    public function changePassword(Request $request)
    {
        // dd ($request->all());

        // 1️⃣ Kiểm tra dữ liệu nhập
        $validator = Validator::make($request->all(), [
            'newPassword' => [
                'required',
                'string',
                'min:6',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'newPassword.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
            'newPassword.regex' => 'Mật khẩu mới không đảm bảo độ phức tạp',
            'confirmPassword.required' => 'Vui lòng xác nhận mật khẩu mới',
            'confirmPassword.same' => 'Xác nhận mật khẩu không khớp',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'changePasswordErrors')->with('activeForm', 'changePass');
        }

        if ($request->oldPassword == $request->newPassword) {
            return redirect()->route('login')->with('error', 'PassWord mới trung PassWord hiện tại!')->with('activeForm', 'changePass');
        }

        // 2️⃣ Lấy thông tin người dùng trong DB
        $getUser = DB::table('user_management')->where('userName', '=', $request->username)->first();

        if (! $getUser) {
            return back()->with('error', 'User Không tồn tại');
        }

        // 3️⃣ Xác thực mật khẩu cũ
        if (! Hash::check($request->oldPassword, $getUser->passWord)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.')->with('activeForm', 'changePass');
        }

        // 4️⃣ Cập nhật mật khẩu mới (hash)
        $newHash = Hash::make($request->newPassword);

        DB::table('user_management')
            ->where('id', $getUser->id)
            ->update(['passWord' => $newHash]);

        $request->session()->put('user', [
            'userId' => $getUser->id,
            'userName' => $getUser->userName,
            'fullName' => $getUser->fullName,
            'passWord' => $request->newPassword,
            'userGroup' => $getUser->userGroup,
            'department' => $getUser->deparment,
        ]);

        // 5️⃣ Ghi log và thông báo
        AuditTrialController::log('ChangePassword', 'NA', 0, 'NA', 'Đổi mật khẩu thành công');

        return redirect()->route('pages.home');
    }
}
