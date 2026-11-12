<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * NGÀY MONG MUỐN GIAO MẶC ĐỊNH của dòng dự trù VPP do hệ thống tự điền.
 *
 * Danh mục VPP không có Thời gian đặt hàng (bỏ 12/11/2026 - VPP do Hành Chánh mua, chia theo
 * nhóm dự trù, xem VppClassification), nên không có cảnh báo "không kịp thời gian đặt hàng"
 * như Vật Tư; chỉ còn khoảng đệm cố định cho phiếu lập sẵn.
 */
class VppLeadTime
{
    /** Phiếu do hệ thống lập sẵn: Ngày mong muốn giao = ngày lập + số ngày này. */
    public const DELIVERY_BUFFER_DAYS = 7;

    /**
     * Ngày mong muốn giao mặc định cho dòng dự trù hệ thống tự điền (phiếu theo chu kỳ, phiếu lập
     * từ tab VPP cần dự trù): ngày lập + DELIVERY_BUFFER_DAYS.
     */
    public static function defaultExpectedDate($base = null): string
    {
        return Carbon::parse($base ?? now())->startOfDay()
            ->addDays(self::DELIVERY_BUFFER_DAYS)
            ->toDateString();
    }
}
