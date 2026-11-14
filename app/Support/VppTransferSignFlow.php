<?php

namespace App\Support;

/**
 * QUY TRÌNH KÝ ĐỀ NGHỊ CHUYỂN VPP LIÊN PHÒNG BAN (gồm Đề nghị VPP gửi Hành Chính) - 2 bước cố
 * định, xem App\Support\TransferSignFlow: quản lý bộ phận phòng đề nghị -> quản lý bộ phận phòng
 * cấp phát (đề nghị VPP: phòng Hành Chính).
 *
 * TABLE / KINDS / SCOPES chỉ còn cho màn Dữ Liệu Gốc "Trình Ký Đề Nghị Liên Phòng Ban VPP" cũ
 * (bảng vpp_transfer_sign_steps, đã ẩn khỏi menu từ 14/11/2026) - quy trình ký KHÔNG đọc nữa.
 */
class VppTransferSignFlow extends TransferSignFlow
{
    public const REQUEST_TABLE = VppTransferRequest::TABLE;

    public const ITEM_TABLE = VppTransferRequest::ITEM_TABLE;

    public const SIGN_TABLE = 'vpp_transfer_signs';

    /** Bảng quy trình khai ở Dữ Liệu Gốc (không còn dùng cho việc ký). */
    public const TABLE = 'vpp_transfer_sign_steps';

    public const KIND_VPP = 'vpp';

    public const KIND_TRANSFER = 'transfer';

    public const KINDS = [
        self::KIND_VPP => 'Đề nghị cấp phát VPP đến HC',
        self::KIND_TRANSFER => 'Đề nghị liên phòng ban khác',
    ];

    public const SCOPE_REQUESTER = 'requester';

    public const SCOPE_RECEIVER = 'receiver';

    public const SCOPE_DEPARTMENT = 'department';

    public const SCOPES = [
        self::SCOPE_REQUESTER => 'Phòng đề nghị',
        self::SCOPE_RECEIVER => 'Phòng được đề nghị',
        self::SCOPE_DEPARTMENT => 'Phòng cố định',
    ];

    /** Loại đề nghị theo phòng nhận: gửi Hành Chính (kho VPP) là VPP, còn lại là liên phòng ban. */
    public static function kindFor(?int $toDepartmentId): string
    {
        return VppStore::isVppDepartment($toDepartmentId) ? self::KIND_VPP : self::KIND_TRANSFER;
    }
}
