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
    /*
     | Vòng đời một dòng đề nghị. Kho CẤP PHÁT là trừ tồn ngay (sinh luôn phiếu sử dụng),
     | sau đó Tổ chốt lại: ghi nhận đã dùng bao nhiêu, hoặc trả toàn bộ về kho.
     |
     | Kho thiếu hàng thì cấp được bao nhiêu hay bấy nhiêu: dòng chuyển sang PARTIAL và
     | vẫn cấp thêm được cho tới khi đủ số đề nghị, lúc đó mới thành ISSUED.
     |      pending -> partial* -> issued -> used | returned
     |      pending -> rejected
     */
    'request_item_statuses' => [
        'pending' => 'Chờ cấp phát',
        'partial' => 'Cấp phát một phần',
        'issued' => 'Đã cấp phát',
        'used' => 'Đã sử dụng',
        'returned' => 'Đã trả về kho',
        'rejected' => 'Bị từ chối',
    ],

    /*
    |--------------------------------------------------------------------------
    | XUẤT KHO THÔNG MINH - ĐỢT LẤY HÀNG
    |--------------------------------------------------------------------------
    */

    /*
     | Vòng đời một ĐỢT LẤY HÀNG - cột material_pick_waves.status.
     |
     |      new -> picking -> picked -> packed -> shipped
     |      (bất kỳ bước nào trước shipped) -> canceled
     |
     | TRỪ TỒN chỉ xảy ra ở bước shipped, đúng lúc hàng rời kho. Bốn trạng thái trước đó
     | chỉ GIỮ CHỖ tồn qua các dòng material_pick_lines còn treo.
     */
    'pick_wave_statuses' => [
        'new' => 'Chờ xuất',
        'picking' => 'Đang lấy',
        'picked' => 'Đã lấy',
        'packed' => 'Đã đóng gói',
        'shipped' => 'Đã xuất',
        'canceled' => 'Đã huỷ',
    ],

    /*
     | Vòng đời một DÒNG lấy hàng - cột material_pick_lines.status.
     | Lấy thiếu (short) vẫn cho xuất đợt, phần còn thiếu treo lại chờ đợt sau.
     */
    'pick_line_statuses' => [
        'pending' => 'Chờ lấy',
        'picked' => 'Đã lấy đủ',
        'short' => 'Lấy thiếu',
        'canceled' => 'Đã bỏ',
    ],

    /*
     | NGƯỠNG CẬN HẠN - số ngày còn lại tới hạn dùng, tính theo hai mức:
     |
     |      warning  (vàng) : nhắc dùng trước, và kịp đặt mua bù
     |      critical (đỏ)   : sát hạn, ưu tiên xuất ngay
     |
     | Ngưỡng nên >= thời gian đặt mua vật tư về tới kho, vì mục đích của cảnh báo là kịp
     | phản ứng chứ không phải để biết. Đổi ở đây, Pick List và ô chọn lô tự đổi theo.
     */
    'near_expiry_days' => [
        'warning' => 60,
        'critical' => 30,
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
