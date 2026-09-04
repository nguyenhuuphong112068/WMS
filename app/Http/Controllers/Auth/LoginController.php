<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /** §11.300(d) - khoá tài khoản sau số lần nhập sai này. */
    private const MAX_FAILED_ATTEMPTS = 5;

    /** §11.300(d) - thời gian khoá, tự mở sau khoảng này (phút). */
    private const LOCK_MINUTES = 15;

    /** §11.300(b) - mật khẩu có hạn dùng bao nhiêu ngày kể từ lần đổi. */
    private const PASSWORD_VALIDITY_DAYS = 90;

    /** §11.300(b) - không cho trùng với bấy nhiêu mật khẩu gần nhất (kể cả hiện tại). */
    private const PASSWORD_HISTORY_DEPTH = 5;

    public function showLogin()
    {
        session()->put(['title' => 'WMS - QUẢN LÝ KHO']);

        return view('login', []);
    }

    public function login(Request $request)
    {
        $username = trim((string) $request->username);

        $getUser = DB::table('user_management')->where('userName', '=', $username)->first();

        if (is_null($getUser)) {
            AuditTrialController::log(
                'Đăng nhập thất bại', 'user_management', 0,
                'userName: '.$username, 'Tài khoản không tồn tại',
                $username !== '' ? $username : 'NA'
            );

            return redirect()->route('login')->with('error', 'User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!')->with('activeForm', 'login');
        }

        if (! $getUser->isActive) {
            AuditTrialController::log(
                'Đăng nhập bị chặn', 'user_management', $getUser->id,
                'isActive: 0', 'Tài khoản đã bị vô hiệu hoá', $getUser->userName
            );

            return redirect()->route('login')->with('error', 'Tài khoản đã bị vô hiệu hoá, vui lòng liên hệ quản trị hệ thống!')->with('activeForm', 'login');
        }

        // Đang trong thời gian bị khoá tạm thời
        if (! empty($getUser->locked_until) && now()->lt(Carbon::parse($getUser->locked_until))) {
            $minutesLeft = now()->diffInMinutes(Carbon::parse($getUser->locked_until)) + 1;

            return redirect()->route('login')
                ->with('error', 'Tài khoản đang bị khoá do nhập sai quá nhiều lần. Vui lòng thử lại sau '.$minutesLeft.' phút.')
                ->with('activeForm', 'login');
        }

        // Sai mật khẩu -> tăng bộ đếm, khoá khi chạm ngưỡng
        if (! Hash::check($request->passWord, $getUser->passWord)) {
            return $this->handleFailedPassword($getUser);
        }

        // Đúng mật khẩu -> xoá bộ đếm / mở khoá
        DB::table('user_management')->where('id', $getUser->id)->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);

        // §11.300(b) - mật khẩu hết hạn thì buộc đổi trước khi vào hệ thống
        if ($this->passwordExpired($getUser)) {
            AuditTrialController::log(
                'Yêu cầu đổi mật khẩu', 'user_management', $getUser->id,
                'changePWdate: '.$getUser->changePWdate, 'Mật khẩu hết hạn sử dụng', $getUser->userName
            );

            return redirect()->route('login')
                ->with('error', 'Mật khẩu đã hết hạn sử dụng. Vui lòng đổi mật khẩu mới để tiếp tục đăng nhập.')
                ->with('activeForm', 'changePass')
                ->with('pwExpiredUser', $getUser->userName);
        }

        $this->establishSession($request, $getUser);

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

        if ($request->oldPassword === $request->newPassword) {
            return redirect()->route('login')->with('error', 'Mật khẩu mới trùng mật khẩu hiện tại!')->with('activeForm', 'changePass');
        }

        // 2️⃣ Lấy thông tin người dùng
        $getUser = DB::table('user_management')->where('userName', '=', trim((string) $request->username))->first();

        if (! $getUser) {
            return back()->with('error', 'User Không tồn tại')->with('activeForm', 'changePass');
        }

        // 3️⃣ Xác thực mật khẩu cũ
        if (! Hash::check($request->oldPassword, $getUser->passWord)) {
            return back()->with('error', 'Mật khẩu hiện tại không đúng.')->with('activeForm', 'changePass');
        }

        // 4️⃣ §11.300(b) - không cho dùng lại mật khẩu cũ
        if ($this->passwordRecentlyUsed($getUser, $request->newPassword)) {
            return back()
                ->with('error', 'Mật khẩu mới trùng với một trong '.self::PASSWORD_HISTORY_DEPTH.' mật khẩu đã dùng gần đây. Vui lòng chọn mật khẩu khác.')
                ->with('activeForm', 'changePass');
        }

        // 5️⃣ Cập nhật mật khẩu mới (hash) + gia hạn + mở khoá
        $newHash = Hash::make($request->newPassword);

        DB::table('user_management')
            ->where('id', $getUser->id)
            ->update([
                'passWord'              => $newHash,
                'changePWdate'          => today()->addDays(self::PASSWORD_VALIDITY_DAYS),
                'failed_login_attempts' => 0,
                'locked_until'          => null,
            ]);

        $this->recordPasswordHistory($getUser->id, $newHash, $getUser->userName);

        $this->establishSession($request, $getUser);

        AuditTrialController::log('ChangePassword', 'user_management', $getUser->id, 'NA', 'Đổi mật khẩu thành công', $getUser->userName);

        return redirect()->route('pages.home');
    }

    /* ==========================================================
     |  HÀM DÙNG CHUNG
     ========================================================== */

    /** Dựng session người dùng - KHÔNG lưu mật khẩu vào session (§11.300). */
    private function establishSession(Request $request, $getUser): void
    {
        $departmentId = $getUser->deparment_id ? (int) $getUser->deparment_id : null;
        $departmentShort = $departmentId
            ? DB::table('deparments')->where('id', $departmentId)->value('shortName')
            : null;
        $roleName = $getUser->role_id
            ? DB::table('roles')->where('id', $getUser->role_id)->value('name')
            : null;
        $companyId = CompanyContext::resolveForDepartment($departmentId);

        $request->session()->put('user', [
            'userId'                 => $getUser->id,
            'userName'               => $getUser->userName,
            'fullName'               => $getUser->fullName,
            'userGroup'              => $roleName,
            'department'             => $departmentShort,
            'department_id'          => $departmentId,
            'selected_department'    => $departmentShort,
            'selected_department_id' => $departmentId,
            'company_id'             => $companyId,
            'company_name'           => CompanyContext::name($companyId),
            // §11.10(d) - mốc hoạt động gần nhất để tự đăng xuất khi để trống 15 phút
            'last_activity_ts'       => now()->timestamp,
        ]);
    }

    /** Xử lý một lần nhập sai mật khẩu: tăng đếm, khoá 15' khi chạm ngưỡng, ghi log. */
    private function handleFailedPassword($getUser)
    {
        $attempts = (int) $getUser->failed_login_attempts + 1;

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $lockedUntil = now()->addMinutes(self::LOCK_MINUTES);

            DB::table('user_management')->where('id', $getUser->id)->update([
                'failed_login_attempts' => 0,
                'locked_until'          => $lockedUntil,
            ]);

            AuditTrialController::log(
                'Khoá tài khoản', 'user_management', $getUser->id,
                'Nhập sai '.self::MAX_FAILED_ATTEMPTS.' lần liên tiếp',
                'locked_until: '.$lockedUntil->format('Y-m-d H:i:s'),
                $getUser->userName
            );

            return redirect()->route('login')
                ->with('error', 'Bạn đã nhập sai mật khẩu '.self::MAX_FAILED_ATTEMPTS.' lần. Tài khoản bị khoá '.self::LOCK_MINUTES.' phút, sau đó tự mở lại.')
                ->with('activeForm', 'login');
        }

        DB::table('user_management')->where('id', $getUser->id)->update([
            'failed_login_attempts' => $attempts,
        ]);

        AuditTrialController::log(
            'Đăng nhập thất bại', 'user_management', $getUser->id,
            'Lần sai thứ '.$attempts.'/'.self::MAX_FAILED_ATTEMPTS, 'Sai mật khẩu', $getUser->userName
        );

        return redirect()->route('login')
            ->with('error', 'PassWord Không Chính Xác, Vui Lòng Đăng Nhập Lại! (sai '.$attempts.'/'.self::MAX_FAILED_ATTEMPTS.' lần)')
            ->with('activeForm', 'login');
    }

    /** Mật khẩu đã quá hạn changePWdate chưa. Dữ liệu cũ chưa có hạn thì bỏ qua. */
    private function passwordExpired($getUser): bool
    {
        if (empty($getUser->changePWdate)) {
            return false;
        }

        return now()->startOfDay()->gte(Carbon::parse($getUser->changePWdate)->startOfDay());
    }

    /** Mật khẩu mới có trùng N hash gần nhất (kể cả hash hiện tại) không. */
    private function passwordRecentlyUsed($getUser, string $newPassword): bool
    {
        if (Hash::check($newPassword, $getUser->passWord)) {
            return true;
        }

        $recent = DB::table('password_histories')
            ->where('user_id', $getUser->id)
            ->orderByDesc('id')
            ->limit(self::PASSWORD_HISTORY_DEPTH)
            ->pluck('password_hash');

        foreach ($recent as $hash) {
            if (Hash::check($newPassword, $hash)) {
                return true;
            }
        }

        return false;
    }

    /** Ghi thêm một dòng lịch sử mật khẩu (bảng chỉ ghi thêm). */
    private function recordPasswordHistory($userId, string $hash, ?string $actor = null): void
    {
        DB::table('password_histories')->insert([
            'user_id'       => $userId,
            'password_hash' => $hash,
            'created_by'    => $actor ?? 'self',
            'created_at'    => now(),
        ]);
    }
}
