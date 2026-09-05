<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user') || empty(session('user.fullName'))) {
             return redirect()->route ('login')->with('error', 'Vui Lòng Đăng Nhập!');
        }

        // Đảm bảo selected_department luôn có giá trị mặc định là bộ phận của user
        if (empty(session('user.selected_department')) || empty(session('user.selected_department_id'))) {
            $userSession = session('user');
            $deptShort = $userSession['department'] ?? null;
            $deptId = $userSession['department_id'] ?? null;

            if (empty($deptShort) || empty($deptId)) {
                $userDb = DB::table('user_management')->where('id', $userSession['userId'] ?? 0)->first();
                if ($userDb && $userDb->deparment_id) {
                    $dept = DB::table('deparments')->where('id', $userDb->deparment_id)->first();
                    if ($dept) {
                        $deptShort = $dept->shortName;
                        $deptId = $dept->id;
                    }
                }
            }

            if ($deptShort && $deptId) {
                $userSession['department'] = $deptShort;
                $userSession['department_id'] = $deptId;
                $userSession['selected_department'] = $deptShort;
                $userSession['selected_department_id'] = $deptId;
                session()->put('user', $userSession);
            }
        }

        return $next($request);
    }
}
