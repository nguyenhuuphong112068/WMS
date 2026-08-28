<?php

/*
|--------------------------------------------------------------------------
| DỰ TRÙ - DỮ LIỆU CỐ ĐỊNH
|--------------------------------------------------------------------------
| Khai báo tập trung tại đây để Controller (kiểm tra luồng, phân quyền) và
| View (đổ nhãn, tô màu trạng thái) cùng đọc một nguồn.
*/

return [

    /*
     | Trạng thái trình ký của một phiếu dự trù - cột estimate_lists.app_status.
     | Luồng: draft -> pending_manager -> pending_director -> approved
     |        Bị từ chối ở bước nào cũng quay về rejected, sửa lại rồi trình ký lại.
     */
    'app_statuses' => [
        'draft' => 'Nháp',
        'pending_manager' => 'Chờ Phó/Trưởng Phòng ký',
        'pending_director' => 'Chờ Ban Giám Đốc ký',
        'approved' => 'Đã phê duyệt',
        'rejected' => 'Bị từ chối',
        'cancelled' => 'Đã huỷ',
    ],

    /*
     | Hai bước trình ký, hiển thị thành thanh theo dõi trên danh sách dự trù.
     | - role   : vai trò được phép ký bước đó (Admin luôn ký được)
     | - from   : trạng thái phiếu trước khi ký
     | - to     : trạng thái phiếu sau khi ký
     */
    'sign_steps' => [
        'manager' => [
            'no' => 1,
            'label' => 'Phó/Trưởng Phòng',
            'roles' => ['Trưởng Phòng', 'Phó Phòng', 'Phó Trưởng Phòng'],
            'from' => 'pending_manager',
            'to' => 'pending_director',
            'signed_by' => 'manager_signed_by',
            'signed_at' => 'manager_signed_at',
        ],
        'director' => [
            'no' => 2,
            'label' => 'Ban Giám Đốc',
            'roles' => ['Ban Giám Đốc'],
            'from' => 'pending_director',
            'to' => 'approved',
            'signed_by' => 'director_signed_by',
            'signed_at' => 'director_signed_at',
        ],
    ],

    /*
     | Trạng thái tiếp nhận của bộ phận Cung Ứng - cột estimate_lists.reception_status.
     | Chỉ có giá trị sau khi phiếu được Ban Giám Đốc phê duyệt.
     */
    'reception_statuses' => [
        'waiting' => 'Chờ tiếp nhận',
        'received' => 'Đang giải quyết',
        'completed' => 'Đã giải quyết',
    ],

    /*
     | Vai trò của bộ phận Cung Ứng - được tiếp nhận và giải quyết phiếu đã duyệt.
     */
    'supply_roles' => ['Cung Ứng', 'Cung Ung', 'Supply'],
];
