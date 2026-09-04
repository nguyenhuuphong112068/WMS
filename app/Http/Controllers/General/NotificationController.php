<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
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
     */
    public static function sendNotification($message, $activityType = 'Thông báo', $referenceId = null, $targetUserIds = 'all', $targetUserGroups = [], $url = null)
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
        ]);

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

    /**
     * Lấy danh sách thông báo của người dùng hiện tại (cho giao diện chuông)
     */
    public function list()
    {
        $userId = session('user')['userId'];

        $notifications = DB::table('notification_recipients as nr')
            ->join('notifications as n', 'nr.notification_id', '=', 'n.id')
            ->leftJoin('user_management as u', 'n.sender_id', '=', 'u.id')
            ->where('nr.user_id', $userId)
            ->select(
                'n.*',
                'u.fullName as sender_name',
                'nr.is_read',
                'nr.read_at'
            )
            ->orderBy('n.created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(Request $request)
    {
        $userId = session('user')['userId'];

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
