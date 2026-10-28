<?php

namespace App\Support;

use App\Http\Controllers\General\HomeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LỊCH SỬ THÔNG BÁO = các đề nghị đã hiện ở trang chủ.
 *
 * Trang chủ tính "Đề nghị chờ bạn xem xét" trực tiếp từ bảng nghiệp vụ nên đề nghị xử lý
 * xong là biến mất. Thanh Thông báo (RightNAV) giữ lại dấu vết của từng đề nghị đó:
 *
 *  - Đề nghị mới thấy lần đầu  → ghi một dòng notifications (review_key) cho riêng người đó, chưa đọc.
 *  - Vẫn còn chờ                → giữ nguyên, cập nhật nội dung nếu bước / trạng thái đổi.
 *  - Không còn trên trang chủ   → resolved_at = lúc phát hiện, thanh Thông báo hiện "Đã xử lý".
 *  - Đã xử lý mà quay lại chờ   → mở lại (vd cùng người ký tiếp bước sau), đưa lên đầu, chưa đọc.
 *
 * Việc "đã xử lý" chỉ xét trong phòng ban đang chọn (scope_department_id) vì việc của phòng
 * phụ thuộc phòng đang đứng - chuyển phòng không có nghĩa đề nghị của phòng kia đã xong.
 *
 * Đồng bộ chạy khi mở trang chủ và khi thanh Thông báo tải lại (tối đa mỗi SYNC_SECONDS).
 */
class ReviewHistory
{
    private const SYNC_SECONDS = 50;

    private const SESSION_KEY = 'review_history_synced';

    /** Đồng bộ nếu lần trước đã quá SYNC_SECONDS hoặc vừa đổi phòng ban - dùng cho thanh Thông báo. */
    public static function syncIfStale(): void
    {
        $last = session(self::SESSION_KEY);
        $departmentId = self::departmentId();

        if ($last && ($last['department_id'] ?? null) === $departmentId && now()->timestamp - ($last['at'] ?? 0) < self::SYNC_SECONDS) {
            return;
        }

        try {
            self::sync(app(HomeController::class)->pendingReviews());
        } catch (\Throwable $e) {
            // Thanh Thông báo vẫn phải mở được dù một nguồn đề nghị lỗi
            report($e);
        }
    }

    /** $rows: đúng danh sách đề nghị trang chủ đang hiện (HomeController::reviewRow). */
    public static function sync(array $rows): void
    {
        $userId = (int) (session('user')['userId'] ?? 0);
        $departmentId = self::departmentId();

        session()->put(self::SESSION_KEY, ['at' => now()->timestamp, 'department_id' => $departmentId]);

        if ($userId <= 0 || ! self::ready()) {
            return;
        }

        $current = [];

        foreach ($rows as $row) {
            $current[self::key($row)] = $row;
        }

        // Đề nghị còn mở + đề nghị đang hiện (để mở lại nếu đã từng xử lý)
        $known = DB::table('notification_recipients as nr')
            ->join('notifications as n', 'nr.notification_id', '=', 'n.id')
            ->where('nr.user_id', $userId)
            ->whereNotNull('n.review_key')
            ->where(function ($query) use ($current) {
                $query->whereNull('nr.resolved_at')
                    ->when($current, fn ($q) => $q->orWhereIn('n.review_key', array_keys($current)));
            })
            ->select('nr.id as recipient_id', 'n.id as notification_id', 'n.review_key', 'n.message', 'nr.resolved_at', 'nr.scope_department_id')
            ->get()
            ->keyBy('review_key');

        $now = now();

        DB::transaction(function () use ($current, $known, $userId, $departmentId, $now) {
            foreach ($current as $key => $row) {
                $message = self::message($row);
                $old = $known->get($key);

                if (! $old) {
                    $notificationId = DB::table('notifications')->insertGetId([
                        'sender_id' => null,
                        'activity_type' => $row['label'],
                        'review_type' => $row['type'],
                        'review_group' => $row['group'],
                        'review_object' => $row['object'],
                        'review_key' => $key,
                        'message' => $message,
                        'reference_id' => null,
                        'url' => $row['url'],
                        'created_at' => self::since($row['since'], $now),
                    ]);

                    DB::table('notification_recipients')->insert([
                        'notification_id' => $notificationId,
                        'user_id' => $userId,
                        'is_read' => 0,
                        'scope_department_id' => $departmentId,
                    ]);

                    continue;
                }

                if ($old->resolved_at !== null) {
                    DB::table('notifications')->where('id', $old->notification_id)
                        ->update(['message' => $message, 'url' => $row['url'], 'created_at' => $now]);
                    DB::table('notification_recipients')->where('id', $old->recipient_id)
                        ->update(['resolved_at' => null, 'is_read' => 0, 'read_at' => null, 'scope_department_id' => $departmentId]);

                    continue;
                }

                if ($old->message !== $message) {
                    DB::table('notifications')->where('id', $old->notification_id)->update(['message' => $message, 'url' => $row['url']]);
                }

                if ((int) $old->scope_department_id !== $departmentId) {
                    DB::table('notification_recipients')->where('id', $old->recipient_id)->update(['scope_department_id' => $departmentId]);
                }
            }

            $resolved = $known
                ->filter(fn ($old) => $old->resolved_at === null && ! isset($current[$old->review_key])
                    && ($old->scope_department_id === null || (int) $old->scope_department_id === $departmentId))
                ->pluck('recipient_id')
                ->all();

            if ($resolved) {
                DB::table('notification_recipients')->whereIn('id', $resolved)->update(['resolved_at' => $now]);
            }
        });
    }

    /** Đánh dấu đã đọc theo khoá đề nghị - bấm một dòng trên thẻ trang chủ. */
    public static function markRead(int $userId, string $key): void
    {
        if (! self::ready()) {
            return;
        }

        DB::table('notification_recipients as nr')
            ->join('notifications as n', 'nr.notification_id', '=', 'n.id')
            ->where('nr.user_id', $userId)
            ->where('n.review_key', $key)
            ->where('nr.is_read', 0)
            ->update(['nr.is_read' => 1, 'nr.read_at' => now()]);
    }

    /**
     * Một đề nghị = loại × đối tượng × mã × tên × đường dẫn. Không lấy meta (bước ký, trạng thái)
     * vì phần đó đổi theo tiến trình mà vẫn là cùng một đề nghị.
     */
    public static function key(array $row): string
    {
        return md5(implode('|', [$row['type'], $row['object'], $row['code'], $row['title'], $row['direction'] ?? '', $row['url']]));
    }

    private static function message(array $row): string
    {
        $direction = match ($row['direction'] ?? null) {
            'in' => '← Từ '.($row['partner'] ?: 'phòng khác'),
            'out' => '→ Đến '.($row['partner'] ?: 'phòng khác'),
            default => null,
        };

        return implode(' · ', array_filter([
            $row['code'] !== 'NA' ? $row['code'] : null,
            $row['title'],
            $row['meta'],
            $direction,
        ]));
    }

    /** Thời điểm bắt đầu chờ của đề nghị; không đọc được hoặc ở tương lai thì lấy bây giờ. */
    private static function since($since, $now)
    {
        try {
            $time = $since ? \Carbon\Carbon::parse($since) : null;
        } catch (\Throwable $e) {
            $time = null;
        }

        return $time && $time->lte($now) ? $time : $now;
    }

    private static function departmentId(): int
    {
        return (int) (session('user')['selected_department_id'] ?? 0);
    }

    /** Cột phân loại cho thông báo kết quả gửi tay (NotificationController::sendNotification). */
    public static function classify(?string $object, ?string $group): array
    {
        if (! self::ready() || (! $object && ! $group)) {
            return [];
        }

        return [
            'review_object' => isset(HomeController::REVIEW_OBJECTS[$object]) ? $object : null,
            'review_group' => isset(HomeController::REVIEW_GROUPS[$group]) ? $group : null,
        ];
    }

    public static function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'review_key')
            && Schema::hasColumn('notification_recipients', 'resolved_at');
    }
}
