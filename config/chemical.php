<?php

/*
|--------------------------------------------------------------------------
| DANH MỤC HOÁ CHẤT - DỮ LIỆU CỐ ĐỊNH
|--------------------------------------------------------------------------
| Khai báo tập trung tại đây để Controller (validate) và View (đổ ô chọn)
| cùng đọc một nguồn, không khai báo lặp ở hai nơi.
*/

return [

    /*
     | Nhóm phân loại hoá chất theo "Danh mục hoá chất chung hiện hành".
     | Khoá = mã lưu xuống cột chemical_categories.classification (dạng JSON).
     */
    'classifications' => [
        'PL1' => 'Hoá chất cơ bản thuộc lĩnh vực công nghiệp hoá chất trọng điểm',
        'PL2' => 'Hoá chất sản xuất, kinh doanh có điều kiện',
        'PL3' => 'Hoá chất cần kiểm soát đặc biệt',
        'PL4' => 'Hoá chất phải xây dựng kế hoạch phòng ngừa, ứng phó sự cố hoá chất',
        'CAM' => 'Hoá chất cấm',
        'N1'  => 'Hoá chất sản xuất, kinh doanh có điều kiện (Phụ lục II_nhóm 1)',
        'N2'  => 'Hỗn hợp chất sản xuất, kinh doanh có điều kiện (Phụ lục II_nhóm 2)',
        'N3'  => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 1_bảng A (Tiền chất công nghiệp))',
        'N4'  => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 1_bảng B (Hoá chất cấm))',
        'N5'  => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng A (Tiền chất công nghiệp))',
        'N6'  => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng B (Hoá chất cấm))',
        'N7'  => 'Hoá chất cần kiểm soát đặc biệt (Phụ lục III_nhóm 2_bảng C (Hoá chất thuộc các công ước quốc tế về hoá chất))',
        'N8'  => 'Hỗn hợp chất cần kiểm soát đặc biệt (Phụ lục III)',
        'N9'  => 'Hoá chất phải xây dựng kế hoạch phòng ngừa, ứng phó sự cố hoá chất (Phụ lục IV_Bảng A)',
        'N10' => 'Hoá chất phải xây dựng kế hoạch phòng ngừa, ứng phó sự cố hoá chất (Phụ lục IV_Bảng B)',
    ],

    /*
     | Gợi ý cho ô "Loại" (cột type). Người dùng vẫn gõ được giá trị khác.
     */
    'types' => [
        'Nguyên liệu',
        'Dung môi',
        'Phụ gia',
        'Chất chuẩn',
        'Hoá chất thí nghiệm',
        'Hoá chất vệ sinh',
    ],

    /*
     | Nhóm cảnh báo an toàn (kiểu GHS) cho ô "Cảnh Báo An Toàn". Một hoá chất được
     | chọn NHIỀU mã cùng lúc, lưu dạng chuỗi JSON các mã xuống
     | chemical_categories.safety_warning, ví dụ ["TOXIC","CORROSIVE"] - cùng cách
     | lưu với cột classification.
     |
     | Khoá = mã cố định, KHÔNG đổi vì đã lưu trong DB. Logo tương ứng từng mã vẽ tại
     | resources/views/pages/shared/safetyPictogram.blade.php, thêm mã mới thì thêm
     | luôn @case cho mã đó ở file logo, không thì hiện logo mặc định (dấu chấm than).
     */
    'safety_warnings' => [
        'TOXIC' => 'Độc/Toxic',
        'CORROSIVE' => 'Ăn mòn/Corrosive',
        'FLAMMABLE' => 'Dễ cháy/Flammable',
        'OXIDIZING' => 'Oxy hoá/Oxidizing',
        'IRRITANT' => 'Kích ứng/Irritant',
        'ENV_HAZARD' => 'Nguy hại môi trường/Environmental hazard',
        'COMPRESSED_GAS' => 'Khí nén/Compressed gas',
        'EXPLOSIVE' => 'Nổ/Explosive',
    ],

    /*
     | NHÃN DÁN LÔ HOÁ CHẤT - in từ màn hình Nhập Hoá Chất.
     |
     | Khổ nhãn tính bằng mm, khớp với cuộn nhãn đang nạp trên máy in Zebra ZD421.
     | Đổi khổ nhãn thì sửa ở đây, trang in tự co giãn theo.
     */
    'label' => [
        'sop_no' => 'QC-SOP-031',
        'form_no' => 'QC/F/106-03',
        'width_mm' => 60,
        'height_mm' => 40,
    ],
];
