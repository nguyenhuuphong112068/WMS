<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHÒNG BAN CHỈ XEM - mọi user chuyển sang xem được mọi phòng ban, nhưng ở phòng nằm ngoài
 * phạm vi làm việc (user_department_view_only) chỉ được xem: chặn mọi request ghi (không phải
 * GET / HEAD) ngay tại đây, kể cả thao tác Controller chưa tự kiểm quyền.
 *
 * Đứng sau SoftSubmit để redirect()->back() báo lỗi cũng được đổi thành JSON khi gửi ngầm.
 */
class ViewOnlyDepartment
{
    /** Thao tác của riêng tài khoản, không gắn với dữ liệu phòng ban. */
    private const ALLOWED_ROUTES = ['login', 'logout', 'changePassword', 'notifications.markAsRead'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()
            || ! session()->has('user')
            || $request->routeIs(...self::ALLOWED_ROUTES)
            || ! user_department_view_only()) {
            return $next($request);
        }

        $message = 'Phòng ban '.(session('user')['selected_department'] ?? '')
            .' ngoài phạm vi làm việc của bạn - chỉ được xem, không thao tác được. '
            .'Chuyển về phòng ban của bạn để thao tác.';

        if ($request->expectsJson() && $request->headers->get(SoftSubmit::HEADER) !== '1') {
            return response()->json(['success' => false, 'message' => $message, 'error' => $message], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
