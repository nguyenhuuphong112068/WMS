<?php

/*
|--------------------------------------------------------------------------
| ĐƠN VỊ TÍNH - NHÓM VÀ QUY ĐỔI
|--------------------------------------------------------------------------
| Khai báo tập trung để Controller, View và App\Support\UnitConverter cùng
| đọc một nguồn.
|
| base       : đơn vị gốc của nhóm, mọi đơn vị trong nhóm quy về đây
| convertible: nhóm có quy đổi được sang nhóm khác hay không
|              mass <-> volume quy đổi được qua tỉ trọng d
|              count (thùng, chai, bao) phụ thuộc quy cách đóng gói của từng
|              mặt hàng nên không quy đổi tự động
*/

return [

    'groups' => [
        'mass' => [
            'label' => 'Khối lượng',
            'base' => 'g',
            'convertible' => true,
        ],
        'volume' => [
            'label' => 'Thể tích',
            'base' => 'ml',
            'convertible' => true,
        ],
        'count' => [
            'label' => 'Đếm / Bao bì',
            'base' => 'đơn vị',
            'convertible' => false,
        ],
    ],

    /*
     | Gợi ý hệ số cho các đơn vị thường gặp, hiện ở màn khai báo Đơn Vị Tính
     | để người dùng nhập nhanh và nhất quán.
     */
    'suggestions' => [
        'mass' => ['µg' => 0.000001, 'mg' => 0.001, 'g' => 1, 'kg' => 1000, 'tấn' => 1000000],
        'volume' => ['µl' => 0.001, 'ml' => 1, 'cc' => 1, 'L' => 1000, 'm3' => 1000000],
        'count' => [
            'cái' => 1, 'chiếc' => 1, 'bộ' => 1, 'đôi' => 1,
            'chai' => 1, 'lọ' => 1, 'ống' => 1, 'can' => 1, 'phuy' => 1, 'xô' => 1,
            'hộp' => 1, 'thùng' => 1, 'bao' => 1, 'túi' => 1, 'kiện' => 1, 'gói' => 1, 'vỉ' => 1,
            'cuộn' => 1, 'tờ' => 1, 'viên' => 1, 'm' => 1,
        ],
    ],
];
