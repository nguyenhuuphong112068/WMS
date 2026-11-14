<?php

namespace App\Support;

/** QUY TRÌNH KÝ ĐỀ NGHỊ CHUYỂN HOÁ CHẤT LIÊN PHÒNG BAN - 2 bước cố định, xem App\Support\TransferSignFlow. */
class ChemicalTransferSignFlow extends TransferSignFlow
{
    public const REQUEST_TABLE = 'chemical_transfer_requests';

    public const ITEM_TABLE = 'chemical_transfer_items';

    public const SIGN_TABLE = 'chemical_transfer_signs';
}
