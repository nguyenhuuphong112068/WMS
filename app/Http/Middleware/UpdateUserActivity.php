<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /** §11.10(d) - tự đăng xuất sau bấy nhiêu giây không thao tác. */
    private const IDLE_TIMEOUT_SECONDS = 15 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = session('user');

        if (! is_array($user) || empty($user['userId'])) {
            return $next($request);
        }

        $now = now()->timestamp;
        $last = (int) ($user['last_activity_ts'] ?? $now);

        // Quá hạn không hoạt động -> huỷ phiên, buộc đăng nhập lại
        if ($now - $last > self::IDLE_TIMEOUT_SECONDS) {
            AuditTrialController::log(
                'Tự đăng xuất', 'NA', 0, 'NA',
                'Hết phiên do không hoạt động '.(self::IDLE_TIMEOUT_SECONDS / 60).' phút',
                $user['userName'] ?? 'NA'
            );

            $request->session()->flush();

            $message = 'Phiên làm việc đã hết hạn do không hoạt động quá '.(self::IDLE_TIMEOUT_SECONDS / 60).' phút. Vui lòng đăng nhập lại.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return redirect()->route('login')->with('error', $message);
        }

        // Còn hạn -> dời mốc hoạt động
        $user['last_activity_ts'] = $now;
        session()->put('user', $user);

        DB::table('user_management')->where('id', $user['userId'])->update(['last_activity' => now()]);

        return $next($request);
    }
}
