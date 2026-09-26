<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * CẢNH BÁO KHÔNG KỊP THỜI GIAN ĐẶT HÀNG của vật tư dự trù.
 *
 * Mốc tính: ngày Ban Giám Đốc duyệt phiếu (khi phiếu đã duyệt), chưa duyệt thì lấy ngày
 * tạo phiếu. Số ngày từ mốc tới "Ngày mong muốn giao" nhỏ hơn "Thời gian đặt hàng"
 * (material_categories.lead_time_days) thì cảnh báo - chỉ cảnh báo, không chặn lưu.
 *
 * Modal thêm vật tư tính lại cùng công thức bằng JS (itemCreate.blade.php).
 */
class MaterialLeadTime
{
    /**
     * null khi không cần cảnh báo; ngược lại ['lead_days' => int, 'available_days' => int, 'base' => 'approved'|'created'].
     */
    public static function check($createdAt, $approvedAt, $expectedDate, $leadDays): ?array
    {
        if (! $expectedDate || $leadDays === null || $leadDays === '' || (int) $leadDays <= 0) {
            return null;
        }

        $base = Carbon::parse($approvedAt ?: $createdAt)->startOfDay();
        $available = (int) $base->diffInDays(Carbon::parse($expectedDate)->startOfDay(), false);

        if ($available >= (int) $leadDays) {
            return null;
        }

        return [
            'lead_days' => (int) $leadDays,
            'available_days' => $available,
            'base' => $approvedAt ? 'approved' : 'created',
        ];
    }

    /** Câu hiển thị trên badge / tooltip. */
    public static function message(array $warn): string
    {
        $baseLabel = $warn['base'] === 'approved' ? 'ngày BGĐ duyệt' : 'ngày tạo phiếu';

        return 'Không kịp thời gian đặt hàng: cần '.$warn['lead_days'].' ngày, từ '.$baseLabel
            .' đến ngày mong muốn giao chỉ còn '.$warn['available_days'].' ngày.';
    }
}
