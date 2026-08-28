<?php

/*
|--------------------------------------------------------------------------
| VẬT TƯ - DỮ LIỆU CỐ ĐỊNH
|--------------------------------------------------------------------------
| Khai báo tập trung để Controller (kiểm tra luồng, phân quyền) và View (đổ
| nhãn, tô màu trạng thái) cùng đọc một nguồn.
*/

return [

    /*
     | TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ - cột material_request_lists.app_status.
     |
     | Luồng: draft -> pending_manager -> (pending_director) -> approved
     |   - Trưởng/Phó Phòng ký là BẮT BUỘC.
     |   - Ban Giám Đốc ký chỉ khi phiếu đặt needs_director = 1 (tuỳ chọn).
     |   Bị từ chối ở bước nào cũng đưa về rejected, sửa lại rồi trình ký lại từ đầu.
     */
    'request_app_statuses' => [
        'draft' => 'Nháp',
        'pending_manager' => 'Chờ Trưởng/Phó Phòng duyệt',
        'pending_director' => 'Chờ Ban Giám Đốc duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Bị từ chối',
        'canceled' => 'Đã huỷ',
    ],

    /*
     | Hai bước ký của đề nghị. 'to' không khai ở đây vì bước Trưởng/Phó Phòng có thể
     | dẫn thẳng tới approved (khi không cần Ban Giám Đốc) - Controller tự tính.
     */
    'request_sign_steps' => [
        'manager' => [
            'no' => 1,
            'label' => 'Trưởng/Phó Phòng',
            'roles' => ['Trưởng Phòng', 'Phó Phòng', 'Phó Trưởng Phòng'],
            'from' => 'pending_manager',
            'signed_by' => 'manager_signed_by',
            'signed_at' => 'manager_signed_at',
        ],
        'director' => [
            'no' => 2,
            'label' => 'Ban Giám Đốc',
            'roles' => ['Ban Giám Đốc'],
            'from' => 'pending_director',
            'signed_by' => 'director_signed_by',
            'signed_at' => 'director_signed_at',
        ],
    ],

    /*
     | Cấp phát của kho sau khi đề nghị được duyệt - cột material_request_lists.issue_status
     | và material_request_items.status.
     */
    'request_issue_statuses' => [
        'waiting' => 'Chờ cấp phát',
        'partial' => 'Cấp phát một phần',
        'completed' => 'Đã cấp phát',
    ],
    'request_item_statuses' => [
        'pending' => 'Chờ cấp phát',
        'issued' => 'Đã cấp phát',
        'rejected' => 'Bị từ chối',
    ],

    /*
     | NHÃN LÔ VẬT TƯ - in từ màn hình Nhập Vật Tư. Mã vạch dạng QR (App\Support\QrCode).
     | Đổi khổ nhãn thì sửa ở đây, trang in tự co giãn theo.
     */
    'label' => [
        'sop_no' => 'QC-SOP-031',
        'form_no' => 'QC/F/106-03',
        'width_mm' => 50,
        'height_mm' => 30,
    ],
];
