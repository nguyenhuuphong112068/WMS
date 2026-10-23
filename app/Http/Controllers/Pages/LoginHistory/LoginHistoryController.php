<?php

namespace App\Http\Controllers\Pages\LoginHistory;

use App\Http\Controllers\Controller;
use App\Support\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * LỊCH SỬ ĐĂNG NHẬP (theo dự án PMS)
 *  - Toàn hệ thống (quyền login_history_view): mọi lượt đăng nhập, phiên đang mở,
 *    thống kê trong ngày, lọc và xuất Excel.
 *  - Của tôi: mỗi người tự xem lịch sử đăng nhập của mình để phát hiện truy cập lạ.
 */
class LoginHistoryController extends Controller
{
    private function query(Request $request)
    {
        return DB::table('login_histories')
            ->leftJoin('user_management', 'user_management.id', '=', 'login_histories.user_id')
            ->leftJoin('deparments', 'deparments.id', '=', 'user_management.deparment_id')
            ->select('login_histories.*', 'user_management.fullName as full_name', 'deparments.shortName as department_code')
            ->whereDate('login_histories.login_at', '>=', $request->input('from', now()->subDays(7)->toDateString()))
            ->whereDate('login_histories.login_at', '<=', $request->input('to', now()->toDateString()))
            ->when($request->filled('user'), fn ($q) => $q->where('login_histories.userName', $request->input('user')))
            ->when($request->filled('status'), fn ($q) => $q->where('login_histories.status', $request->input('status')))
            ->when($request->filled('ip'), fn ($q) => $q->where('login_histories.ip_address', 'like', $request->input('ip') . '%'))
            ->when($request->filled('department'), fn ($q) => $q->where('user_management.deparment_id', $request->input('department')))
            ->orderByDesc('login_histories.id');
    }

    public function index(Request $request)
    {
        abort_unless(user_has_permission(session('user')['userId'], 'login_history_view'), 403, 'Bạn không có quyền xem Lịch Sử Đăng Nhập toàn hệ thống');

        if ($request->input('export') === 'csv') {
            return $this->export($request);
        }

        $today = DB::table('login_histories')->whereDate('login_at', today())
            ->selectRaw("SUM(status = 'success') as success, SUM(status <> 'success') as failed, COUNT(DISTINCT CASE WHEN status = 'success' THEN user_id END) as users")
            ->first();

        $online = LoginHistory::activeQuery()
            ->leftJoin('user_management', 'user_management.id', '=', 'login_histories.user_id')
            ->orderByDesc('login_histories.last_seen_at')
            ->get(['login_histories.*', 'user_management.fullName as full_name']);

        session()->put(['title' => 'LỊCH SỬ ĐĂNG NHẬP']);

        return view('pages.loginHistory.list', [
            'logs'        => $this->query($request)->paginate(50)->withQueryString(),
            'today'       => $today,
            'online'      => $online,
            'from'        => $request->input('from', now()->subDays(7)->toDateString()),
            'to'          => $request->input('to', now()->toDateString()),
            'users'       => DB::table('login_histories')->distinct()->orderBy('userName')->pluck('userName'),
            'departments' => DB::table('deparments')->where('isActive', 1)->orderBy('name')->get(['id', 'name', 'shortName']),
        ]);
    }

    public function mine()
    {
        $user = session('user');

        $logs = DB::table('login_histories')
            ->where(fn ($q) => $q->where('user_id', $user['userId'])->orWhere('userName', $user['userName']))
            ->orderByDesc('id')
            ->paginate(30);

        session()->put(['title' => 'LỊCH SỬ ĐĂNG NHẬP CỦA TÔI']);

        return view('pages.loginHistory.mine', [
            'logs'      => $logs,
            'currentId' => session(LoginHistory::SESSION_KEY),
            'failed30'  => DB::table('login_histories')->where('userName', $user['userName'])
                ->whereIn('status', ['failed', 'locked'])->where('login_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    private function export(Request $request)
    {
        $rows = $this->query($request)->limit(50000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Thời điểm đăng nhập', 'Tài khoản', 'Họ tên', 'Phòng ban', 'Kết quả', 'Lý do', 'IP', 'Trình duyệt', 'Hệ điều hành',
                'Thao tác cuối', 'Kết thúc phiên', 'Cách kết thúc', 'Thời lượng (phút)']);
            foreach ($rows as $r) {
                $end = $r->logout_at ?? $r->last_seen_at;
                fputcsv($out, [
                    Carbon::parse($r->login_at)->format('d/m/Y H:i:s'), $r->userName, $r->full_name, $r->department_code,
                    LoginHistory::STATUSES[$r->status]['label'] ?? $r->status, $r->reason, $r->ip_address, $r->browser, $r->platform,
                    $r->last_seen_at ? Carbon::parse($r->last_seen_at)->format('d/m/Y H:i:s') : '',
                    $r->logout_at ? Carbon::parse($r->logout_at)->format('d/m/Y H:i:s') : '',
                    LoginHistory::LOGOUT_TYPES[$r->logout_type] ?? '',
                    $r->status === 'success' && $end ? (int) Carbon::parse($r->login_at)->diffInMinutes(Carbon::parse($end)) : '',
                ]);
            }
            fclose($out);
        }, 'WMS_lich_su_dang_nhap_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
