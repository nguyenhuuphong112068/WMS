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
     | PHÂN LOẠI HOÁ CHẤT theo 10 nhóm của Nghị định 24/2026/NĐ-CP đã chuyển sang
     | App\Support\ChemicalClassification (suy tự động từ dữ liệu gốc "Tên Hoạt Chất" +
     | "Tên Hoá Chất"). Không còn cột chemical_categories.classification, không còn bước
     | tick tay ở màn Danh Mục Hoá Chất. Bộ lọc / chip lấy nhãn từ
     | ChemicalClassification::labels() (mã N1..N10).
     */

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
     | NGƯỠNG TỒN TRỮ - Phụ lục IV Nghị định 24/2026/NĐ-CP.
     |
     | Ngưỡng khai ở dữ liệu gốc active_ingredients.threshold_kg. Ở đây chỉ khai mức
     | tỉ lệ để bắt đầu cảnh báo (so với ngưỡng) và câu trích dẫn dùng chung.
     |
     | warn_ratio : tồn / ngưỡng >= mức này thì cảnh báo vàng; >= 1.0 là cảnh báo đỏ.
     |
     | Diện đối chiếu ngưỡng nay SUY tự động: mã danh mục có hoạt chất thuộc nhóm 9
     | (Phụ lục IV Bảng A) hoặc là hỗn hợp thuộc nhóm 10 (Bảng B) thì tự vào diện, không
     | cần tick tay nữa (App\Support\ActiveIngredientThreshold / MixtureHazardThreshold).
     */
    'threshold_iv' => [
        'warn_ratio' => 0.8,
        'legal_ref' => 'Nghị định 24/2026/NĐ-CP - Phụ lục IV',
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
        // Độ phân giải đầu in Zebra (ZD421: bản 203 hoặc 300 dpi - xem tem dưới đáy máy).
        'dpi' => 300,
    ],
];
