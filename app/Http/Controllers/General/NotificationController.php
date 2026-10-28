<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Support\ReviewHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * HÀM GỬI THÔNG BÁO DÙNG CHUNG TRONG TOÀN PROJECT
     * $message: Nội dung
     * $activityType: Loại hoạt động (Tạo mới, Phê duyệt, Hủy...)
     * $referenceId: ID liên quan (ví dụ plan_id)
     * $targetUserIds: Mảng ID người nhận cụ thể (ví dụ [1, 2, 5])
     * $targetUserGroups: Mảng nhóm người nhận (ví dụ ['Admin', 'QC'])
     * $object / $group: phân loại như thẻ trang chủ (HomeController::REVIEW_OBJECTS / REVIEW_GROUPS)
     *
     * Chỉ dùng cho thông báo KẾT QUẢ (đã duyệt, bị từ chối, kết quả hiệu chuẩn...). Việc đang chờ
     * ai đó làm thì không gửi ở đây: trang chủ tự ghi vào lịch sử (App\Support\ReviewHistory).
     */
    public static function sendNotification($message, $activityType = 'Thông báo', $referenceId = null, $targetUserIds = 'all', $targetUserGroups = [], $url = null, ?string $object = null, ?string $group = null)
    {
        $senderId = session('user')['userId'] ?? 0;

        // 1. Tạo bản ghi thông báo chính
        $notificationId = DB::table('notifications')->insertGetId([
            'sender_id' => session('user')['userId'],
            'activity_type' => $activityType,
            'message' => $message,
            'reference_id' => $referenceId,
            'url' => $url,
            'created_at' => now(),
        ] + ReviewHistory::classify($object, $group));

        // 2. Thu thập tất cả người nhận
        if ($targetUserIds === 'all') {
            $allRecipientIds = DB::table('user_management')->where('isActive', 1)->pluck('id')->toArray();
        } else {
            $allRecipientIds = is_array($targetUserIds) ? $targetUserIds : [$targetUserIds];
        }

        // Nếu có truyền nhóm, lấy tất cả ID của những người thuộc nhóm đó
        if (!empty($targetUserGroups)) {
            $targetUserGroups = is_array($targetUserGroups) ? $targetUserGroups : [$targetUserGroups];

            // Role chính trên user_management.role_id
            $byPrimaryRole = DB::table('user_management')
                ->join('roles', 'roles.id', '=', 'user_management.role_id')
                ->whereIn('roles.name', $targetUserGroups)
                ->where('user_management.isActive', 1)
                ->pluck('user_management.id')
                ->toArray();

            // Role gán thêm qua bảng trung gian user_role
            $byAssignedRole = DB::table('user_management')
                ->join('user_role', 'user_role.user_id', '=', 'user_management.id')
                ->join('roles', 'roles.id', '=', 'user_role.role_id')
                ->whereIn('roles.name', $targetUserGroups)
                ->where('user_management.isActive', 1)
                ->pluck('user_management.id')
                ->toArray();

            $allRecipientIds = array_merge($allRecipientIds, $byPrimaryRole, $byAssignedRole);
        }

        // 3. Loại bỏ ID trùng lặp và ID người gửi
        $allRecipientIds = array_unique($allRecipientIds);
        $allRecipientIds = array_filter($allRecipientIds, function ($id) use ($senderId) {
            return $id != $senderId && !is_null($id) && $id !== '';
        });

        // 4. Lưu vào bảng recipients
        $dataInsert = [];
        foreach ($allRecipientIds as $userId) {
            $dataInsert[] = [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'is_read' => 0,
            ];
        }

        if (!empty($dataInsert)) {
            DB::table('notification_recipients')->insert($dataInsert);
        }

        return $notificationId;
    }

    /** Số thông báo mỗi lần tải; "Xem thêm thông báo cũ hơn" tải tiếp từ dòng cuối. */
    private const PAGE = 30;

    /**
     * Thanh Thông báo (RightNAV) = lịch sử các đề nghị trang chủ + thông báo kết quả.
     *
     * Đồng bộ lịch sử trước khi đọc để đề nghị mới hiện ra trang chủ cũng có ngay ở đây.
     * ?filter=unread|pending, ?before=<id notification_recipients> để tải trang cũ hơn.
     */
    public function list(Request $request)
    {
        $userId = session('user')['userId'];

        ReviewHistory::syncIfStale();

        $hasReview = ReviewHistory::ready();
        $base = DB::table('notification_recipients as nr')
            ->join('notifications as n', 'nr.notification_id', '=', 'n.id')
            ->where('nr.user_id', $userId);

        $query = (clone $base)
            ->leftJoin('user_management as u', 'n.sender_id', '=', 'u.id')
            ->when($request->query('filter') === 'unread', fn ($q) => $q->where('nr.is_read', 0))
            ->when($hasReview && $request->query('filter') === 'pending', fn ($q) => $q->whereNotNull('n.review_type')->whereNull('nr.resolved_at'))
            ->when($request->filled('before'), function ($q) use ($request, $base) {
                $before = (clone $base)->where('nr.id', (int) $request->query('before'))->value('n.created_at');
                $q->where(fn ($w) => $w->where('n.created_at', '<', $before)
                    ->orWhere(fn ($e) => $e->where('n.created_at', $before)->where('nr.id', '<', (int) $request->query('before'))));
            })
            ->select(
                'n.id',
                'nr.id as recipient_id',
                'n.message',
                'n.activity_type',
                'n.url',
                'n.created_at',
                // Thông báo hệ thống tự sinh (sender_id = null) không có người gửi
                DB::raw("COALESCE(u.fullName, 'Hệ thống') as sender_name"),
                'nr.is_read',
                'nr.read_at'
            )
            ->when($hasReview, fn ($q) => $q->addSelect('n.review_type', 'n.review_group', 'n.review_object', 'n.review_key', 'nr.resolved_at'))
            ->orderByDesc('n.created_at')
            ->orderByDesc('nr.id')
            ->limit(self::PAGE + 1);

        $items = $query->get();
        $hasMore = $items->count() > self::PAGE;
        $items = $items->take(self::PAGE)->map(function ($n) {
            $type = HomeController::REVIEW_TYPES[$n->review_type ?? ''] ?? null;
            $n->actor = $type['actor'] ?? null;
            $n->review_group = $n->review_group ?? ($type['group'] ?? null);
            $n->resolved_at = $n->resolved_at ?? null;

            return $n;
        })->values();

        return response()->json([
            'items' => $items,
            'has_more' => $hasMore,
            'unread' => (clone $base)->where('nr.is_read', 0)->count(),
            'pending' => $hasReview ? (clone $base)->whereNotNull('n.review_type')->whereNull('nr.resolved_at')->count() : 0,
        ]);
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(Request $request)
    {
        $userId = session('user')['userId'];

        // Bấm một dòng trên thẻ trang chủ: đánh dấu theo khoá đề nghị
        if ($request->filled('review_key')) {
            ReviewHistory::markRead((int) $userId, (string) $request->review_key);

            return response()->json(['success' => true]);
        }

        if ($request->boolean('all')) {
            DB::table('notification_recipients')->where('user_id', $userId)->where('is_read', 0)
                ->update(['is_read' => 1, 'read_at' => now()]);

            return response()->json(['success' => true]);
        }

        DB::table('notification_recipients')
            ->where('notification_id', $request->notification_id)
            ->where('user_id', $userId)
            ->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }
}
