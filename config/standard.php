<?php

/*
|--------------------------------------------------------------------------
| CHẤT CHUẨN - DỮ LIỆU CỐ ĐỊNH
|--------------------------------------------------------------------------
| Khai báo tập trung tại đây để Controller (validate, sinh mã) và View (đổ ô
| tick, đổ bộ lọc) cùng đọc một nguồn, không khai báo lặp ở hai nơi.
*/

return [

    /*
     | PHÂN NHÓM CHUẨN - cột standard_categories.groups (lưu dạng JSON các khoá).
     |
     | Một chất chuẩn được xếp vào NHIỀU nhóm cùng lúc, đúng như ô tick trên màn
     | hình khai báo danh mục.
     |
     | - khoá  : mã lưu xuống DB, KHÔNG đổi vì dữ liệu cũ đang dùng.
     | - no    : số thứ tự hiển thị trên bộ lọc (1 - PRS, 2 - Imp.RS...).
     | - name  : tên tiếng Việt của nhóm.
     | - short : cách viết tắt quen dùng trên chứng từ giấy (có dấu chấm).
     | - code  : PHẦN MÃ NHÓM ĐƯA VÀO MÃ ỐNG CHUẨN. Chỉ chữ và số vì mã ống chuẩn
     |           còn được in thành mã vạch, nên "Imp.RS" vào mã là "IMP".
     |
     | Mã ống chuẩn = deparments.shortName + code + yy + mm + số thứ tự (4 chữ số)
     | Ví dụ: QC1 + VKN + 26 + 01 + 0036 -> QC1VKN26010036
     | Xem App\Support\StandardCode.
     */
    'groups' => [
        'PRS' => ['no' => 1, 'name' => 'Chuẩn Chính', 'short' => 'PRS', 'code' => 'PRS'],
        'IMPRS' => ['no' => 2, 'name' => 'Chuẩn Tạp', 'short' => 'Imp.RS', 'code' => 'IMP'],
        'CTC' => ['no' => 3, 'name' => 'Chuẩn Thứ Cấp', 'short' => 'CTC', 'code' => 'CTC'],
        'VKN' => ['no' => 4, 'name' => 'Chuẩn Viện', 'short' => 'VKN', 'code' => 'VKN'],
        'CN' => ['no' => 5, 'name' => 'Chuẩn Nhập Ngoại', 'short' => 'CN', 'code' => 'CN'],
        'CHC' => ['no' => 6, 'name' => 'Chuẩn Hoá Chất', 'short' => 'CHC', 'code' => 'CHC'],
        'CNL' => ['no' => 7, 'name' => 'Chuẩn Nguyên Liệu', 'short' => 'CNL', 'code' => 'CNL'],
    ],

    /*
     | NHÃN DÁN ỐNG CHUẨN - in từ màn hình Nhập Chất Chuẩn.
     |
     | Ống chuẩn nhỏ hơn chai hoá chất nên khổ nhãn cũng nhỏ hơn. Đổi khổ nhãn thì
     | sửa ở đây, trang in tự co giãn theo.
     */
    'label' => [
        'sop_no' => 'QC-SOP-031',
        'form_no' => 'QC/F/106-03',
        'width_mm' => 50,
        'height_mm' => 30,
    ],
];
