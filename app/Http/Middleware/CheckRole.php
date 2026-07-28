<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Dùng trong route: ->middleware('role:Admin,Production_Manager')
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!session()->has('user') || empty(session('user.fullName'))) {
            return redirect()->route('login')->with('error', 'Vui Lòng Đăng Nhập!');
        }

        if (!user_has_any_role(session('user.userId'), $roles)) {
            return redirect()->back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }

        return $next($request);
    }
}
