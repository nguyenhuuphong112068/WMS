<?php

namespace App\Support;

/**
 * QUY TRÌNH KÝ ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN - 2 bước cố định, xem App\Support\TransferSignFlow.
 *
 * TABLE / KINDS / SCOPES chỉ còn cho màn Dữ Liệu Gốc "Trình Ký Đề Nghị Liên Phòng Ban" cũ
 * (bảng material_transfer_sign_steps, đã ẩn khỏi menu từ 14/11/2026) - quy trình ký KHÔNG đọc nữa.
 */
class MaterialTransferSignFlow extends TransferSignFlow
{
    public const REQUEST_TABLE = MaterialTransferRequest::TABLE;

    public const ITEM_TABLE = MaterialTransferRequest::ITEM_TABLE;

    public const SIGN_TABLE = 'material_transfer_signs';

    /** Bảng quy trình khai ở Dữ Liệu Gốc (không còn dùng cho việc ký). */
    public const TABLE = 'material_transfer_sign_steps';

    public const KIND_TRANSFER = 'transfer';

    public const KINDS = [
        self::KIND_TRANSFER => 'Đề nghị liên phòng ban',
    ];

    public const SCOPE_REQUESTER = 'requester';

    public const SCOPE_RECEIVER = 'receiver';

    public const SCOPE_DEPARTMENT = 'department';

    public const SCOPES = [
        self::SCOPE_REQUESTER => 'Phòng đề nghị',
        self::SCOPE_RECEIVER => 'Phòng được đề nghị',
        self::SCOPE_DEPARTMENT => 'Phòng cố định',
    ];

    /** Loại đề nghị theo phòng nhận - ở Vật Tư mọi phòng nhận đều là liên phòng ban. */
    public static function kindFor(?int $toDepartmentId): string
    {
        return self::KIND_TRANSFER;
    }
}
