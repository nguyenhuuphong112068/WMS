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
     | Trạng thái trình ký của một phiếu dự trù - cột <loại>_estimates.app_status.
     | Luồng: draft -> pending_sign -> approved
     |        Người lập phiếu tự khai SỐ BƯỚC KÝ và NGƯỜI KÝ đích danh từng bước (bảng
     |        <loại>_estimate_signs). Tối thiểu 2 bước, bước CUỐI bắt buộc là Ban Giám Đốc.
     |        Bị từ chối ở bước nào cũng quay về rejected, sửa lại rồi trình ký lại từ đầu.
     */
    'app_statuses' => [
        'draft' => 'Nháp',
        'pending_sign' => 'Chờ ký duyệt',
        'approved' => 'Đã phê duyệt',
        'rejected' => 'Bị từ chối',
        'cancelled' => 'Đã huỷ',
    ],

    /*
     | Trạng thái một BƯỚC KÝ - cột <loại>_estimate_signs.status. Bước đang chờ ký là
     | bước 'pending' có step_no nhỏ nhất, trùng với <loại>_estimates.current_step.
     */
    'sign_statuses' => [
        'pending' => 'Chờ ký',
        'signed' => 'Đã ký',
        'rejected' => 'Từ chối',
    ],

    /*
     | QUY TRÌNH KÝ CỐ ĐỊNH 2 BƯỚC của phiếu dự trù:
     |   Bước 1 - QUẢN LÝ BỘ PHẬN của phòng lập phiếu: không chỉ định đích danh, MỌI người
     |            thuộc vai trò dưới đây trong phòng đó đều ký được (xem App\Support\EstimateSigner).
     |   Bước 2 - BAN GIÁM ĐỐC: người lập bắt buộc chọn đích danh một người thuộc bod_roles.
     | Tên vai trò giữ đúng như bảng roles (kể cả "Bộ Phân" thiếu dấu).
     */
    'department_manager_roles' => ['Quản Lý Bộ Phân'],

    'bod_roles' => ['Ban Giám Đốc'],

    /*
     | Vai trò cấp phòng - dùng khi migration chuyển phiếu từ luồng 2 bước cố định cũ
     | sang luồng động (sinh bước ký role-based) và để map nhãn lịch sử cũ.
     */
    'manager_roles' => ['Trưởng Phòng', 'Phó Phòng', 'Phó Trưởng Phòng'],

    /*
     | LUỒNG CŨ (2 bước cố định theo vai trò) - GIỮ LẠI CHỈ ĐỂ:
     |   - migration 2026_09_09_000100 chuyển phiếu đang dở dang sang luồng động
     |   - map nhãn cột "step" của các dòng nhật ký trình ký ghi trước khi đổi luồng
     | Không dùng cho phiếu mới.
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
     | Trạng thái tiếp nhận của bộ phận Cung Ứng - cột <loại>_estimates.reception_status.
     | Chỉ có giá trị sau khi phiếu được ký duyệt đủ các bước.
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
