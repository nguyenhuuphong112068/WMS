<?php

namespace App\Support;

/** QUY TRÌNH KÝ ĐỀ NGHỊ CHUYỂN CHẤT CHUẨN LIÊN PHÒNG BAN - 2 bước cố định, xem App\Support\TransferSignFlow. */
class StandardTransferSignFlow extends TransferSignFlow
{
    public const REQUEST_TABLE = 'standard_transfer_requests';

    public const ITEM_TABLE = 'standard_transfer_items';

    public const SIGN_TABLE = 'standard_transfer_signs';
}
