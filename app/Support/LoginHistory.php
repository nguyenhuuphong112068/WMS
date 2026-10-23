<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ghi lịch sử đăng nhập (theo dự án PMS). Khác audittriallog ở chỗ mỗi phiên là MỘT dòng có cả
 * thời điểm vào / thao tác cuối / ra, nên xem được thời lượng phiên, phiên đang mở, nguồn truy cập.
 *
 * WMS tự đăng xuất sau IDLE_MINUTES phút không thao tác (UpdateUserActivity) — phiên kết thúc kiểu
 * 'timeout'. Phiên đóng trình duyệt mà không quay lại được coi là kết thúc tại lần thao tác cuối.
 */
class LoginHistory
{
    public const SESSION_KEY = 'login_history_id';

    /** §11.10(d) - số phút không thao tác thì tự đăng xuất. */
    public const IDLE_MINUTES = 15;

    public const STATUSES = [
        'success'  => ['label' => 'Thành công', 'badge' => 'badge-success'],
        'failed'   => ['label' => 'Sai mật khẩu / tài khoản', 'badge' => 'badge-warning'],
        'locked'   => ['label' => 'Bị khoá', 'badge' => 'badge-danger'],
        'disabled' => ['label' => 'Tài khoản vô hiệu hoá', 'badge' => 'badge-secondary'],
        'pwchange' => ['label' => 'Phải đổi mật khẩu', 'badge' => 'badge-info'],
    ];

    public const LOGOUT_TYPES = [
        'manual'  => 'Tự đăng xuất',
        'timeout' => 'Hết phiên (không thao tác ' . self::IDLE_MINUTES . ' phút)',
    ];

    /** Đăng nhập thành công — giữ id dòng trong session để cập nhật khi kết thúc phiên. */
    public static function success(int $userId, string $userName): void
    {
        $id = DB::table('login_histories')->insertGetId(self::base($userName, $userId) + [
            'status'       => 'success',
            'session_id'   => session()->getId(),
            'login_at'     => now(),
            'last_seen_at' => now(),
        ]);

        session([self::SESSION_KEY => $id, 'login_history_touched_ts' => now()->timestamp]);
    }

    /** Lần đăng nhập không vào được hệ thống: failed | locked | disabled | pwchange. */
    public static function failure(string $status, string $userName, ?int $userId, string $reason): void
    {
        DB::table('login_histories')->insert(self::base($userName, $userId) + [
            'status'   => $status,
            'reason'   => mb_substr($reason, 0, 255),
            'login_at' => now(),
        ]);
    }

    /** Kết thúc phiên hiện tại (gọi TRƯỚC khi xoá session). */
    public static function end(string $type): void
    {
        if ($id = session(self::SESSION_KEY)) {
            $row = ['logout_at' => now(), 'logout_type' => $type];

            // Hết phiên do không thao tác: giữ nguyên lần thao tác cuối thật sự
            if ($type !== 'timeout') {
                $row['last_seen_at'] = now();
            }

            DB::table('login_histories')->where('id', $id)->whereNull('logout_at')->update($row);
        }
    }

    /** Cập nhật lần thao tác gần nhất (tối đa 1 lần/phút để đỡ ghi DB). */
    public static function touch(): void
    {
        $id = session(self::SESSION_KEY);
        $now = now()->timestamp;

        if ($id && $now - (int) session('login_history_touched_ts', 0) > 60) {
            DB::table('login_histories')->where('id', $id)->update(['last_seen_at' => now()]);
            session(['login_history_touched_ts' => $now]);
        }
    }

    public static function idleMinutes(): int
    {
        return self::IDLE_MINUTES;
    }

    /** Phiên còn mở: chưa đăng xuất và còn thao tác trong IDLE_MINUTES phút gần đây. */
    public static function activeQuery()
    {
        return DB::table('login_histories')
            ->where('login_histories.status', 'success')
            ->whereNull('login_histories.logout_at')
            ->where('login_histories.last_seen_at', '>=', now()->subMinutes(self::idleMinutes()));
    }

    private static function base(string $userName, ?int $userId): array
    {
        $agent = (string) request()->userAgent();
        [$browser, $platform] = self::parseAgent($agent);

        return [
            'user_id'    => $userId,
            'userName'   => mb_substr($userName !== '' ? $userName : 'NA', 0, 60),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr($agent, 0, 500),
            'browser'    => $browser,
            'platform'   => $platform,
        ];
    }

    /** Nhận diện trình duyệt / hệ điều hành đơn giản từ User-Agent. */
    public static function parseAgent(string $ua): array
    {
        $browser = match (true) {
            (bool) preg_match('/Edg\/([\d]+)/', $ua, $m)              => 'Edge ' . $m[1],
            (bool) preg_match('/OPR\/([\d]+)/', $ua, $m)              => 'Opera ' . $m[1],
            (bool) preg_match('/Firefox\/([\d]+)/', $ua, $m)          => 'Firefox ' . $m[1],
            (bool) preg_match('/(?:Chrome|CriOS)\/([\d]+)/', $ua, $m) => 'Chrome ' . $m[1],
            (bool) preg_match('/Version\/([\d]+).*Safari/', $ua, $m)  => 'Safari ' . $m[1],
            str_contains($ua, 'curl')                                 => 'curl',
            $ua === ''                                                => null,
            default                                                   => 'Khác',
        };

        $platform = match (true) {
            str_contains($ua, 'Windows NT 10')      => 'Windows 10/11',
            str_contains($ua, 'Windows')            => 'Windows',
            str_contains($ua, 'Android')            => 'Android',
            (bool) preg_match('/iPhone|iPad/', $ua) => 'iOS',
            str_contains($ua, 'Mac OS X')           => 'macOS',
            str_contains($ua, 'Linux')              => 'Linux',
            default                                 => null,
        };

        return [$browser, $platform];
    }
}
