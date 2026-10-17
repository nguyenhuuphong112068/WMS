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
     | chemical_categories.safety_warning, ví dụ ["TOXIC","CORROSIVE_ACID"].
     |
     | 11 mã đầu theo đúng thứ tự "Sơ đồ lưu trữ hoá chất theo hình đồ cảnh báo" (GHS)
     | của phòng KTCL - chính là các nhóm dùng để xét tương kỵ ở storage_incompatible
     | bên dưới. Hai mã cuối (khí nén, nổ) sơ đồ không có nên chỉ để hiển thị / in nhãn.
     |
     | Nhãn chỉ ghi tiếng Việt (hiện ở modal, bảng danh mục, lịch sử và nhãn in lô).
     |
     | Khoá = mã cố định, KHÔNG đổi vì đã lưu trong DB. Logo tương ứng từng mã vẽ tại
     | resources/views/pages/shared/safetyPictogram.blade.php, thêm mã mới thì thêm
     | luôn @case cho mã đó ở file logo, không thì hiện logo mặc định (dấu chấm than).
     */
    'safety_warnings' => [
        'FLAMMABLE_LIQUID' => 'Lỏng dễ cháy',
        'FLAMMABLE_SOLID' => 'Rắn dễ cháy',
        'WATER_REACTIVE' => 'Gặp nước sinh khí dễ cháy',
        'PYROPHORIC' => 'Dễ tự bốc cháy',
        'CORROSIVE_ACID' => 'Ăn mòn nhóm axit',
        'CORROSIVE_BASE' => 'Ăn mòn nhóm bazơ',
        'OXIDIZING' => 'Oxy hoá',
        'TOXIC' => 'Độc',
        'IRRITANT' => 'Có hại, kích ứng',
        'HEALTH_HAZARD' => 'Nguy hiểm sức khoẻ',
        'ENV_HAZARD' => 'Nguy hại môi trường',
        'COMPRESSED_GAS' => 'Khí nén',
        'EXPLOSIVE' => 'Nổ',
    ],

    /*
     | Mã cũ trước khi tách theo sơ đồ (migration 2026_09_23_200000 đã đổi FLAMMABLE ->
     | FLAMMABLE_LIQUID, CORROSIVE -> CORROSIVE_ACID ở danh mục). Chỉ còn nằm trong ảnh
     | chụp lịch sử cũ - khai nhãn ở đây để modal lịch sử vẫn hiện chữ, không cho chọn mới.
     */
    'safety_warnings_legacy' => [
        'FLAMMABLE' => 'Dễ cháy',
        'CORROSIVE' => 'Ăn mòn',
    ],

    /*
     | TƯƠNG KỴ KHI LƯU TRỮ - chép các ô "x - không được lưu trữ cùng nhau" của sơ đồ.
     | Mỗi dòng của sơ đồ là một khoá, giá trị là các cột bị "x". Sơ đồ đối xứng; lỡ khai
     | thiếu một chiều thì App\Support\ChemicalCompatibility vẫn tự lấy đối xứng.
     | Mã không có ở đây (khí nén, nổ) thì không xét tương kỵ.
     */
    'storage_incompatible' => [
        'FLAMMABLE_LIQUID' => ['FLAMMABLE_SOLID', 'WATER_REACTIVE', 'PYROPHORIC', 'CORROSIVE_ACID', 'OXIDIZING'],
        'FLAMMABLE_SOLID' => ['FLAMMABLE_LIQUID', 'WATER_REACTIVE', 'PYROPHORIC', 'CORROSIVE_ACID', 'OXIDIZING'],
        'WATER_REACTIVE' => ['FLAMMABLE_LIQUID', 'FLAMMABLE_SOLID', 'PYROPHORIC', 'CORROSIVE_ACID', 'OXIDIZING', 'IRRITANT', 'HEALTH_HAZARD', 'ENV_HAZARD'],
        'PYROPHORIC' => ['FLAMMABLE_LIQUID', 'FLAMMABLE_SOLID', 'WATER_REACTIVE', 'CORROSIVE_ACID', 'OXIDIZING'],
        'CORROSIVE_ACID' => ['FLAMMABLE_LIQUID', 'FLAMMABLE_SOLID', 'WATER_REACTIVE', 'PYROPHORIC', 'CORROSIVE_BASE', 'TOXIC'],
        'CORROSIVE_BASE' => ['CORROSIVE_ACID'],
        'OXIDIZING' => ['FLAMMABLE_LIQUID', 'FLAMMABLE_SOLID', 'WATER_REACTIVE', 'PYROPHORIC'],
        'TOXIC' => ['CORROSIVE_ACID'],
        'IRRITANT' => ['WATER_REACTIVE'],
        'HEALTH_HAZARD' => ['WATER_REACTIVE'],
        'ENV_HAZARD' => ['WATER_REACTIVE'],
    ],

    /*
     | Thế nào là "gần nhau" khi xét tương kỵ: hai hoá chất cùng một cấp định khu này.
     |   location  : cùng một vị trí (ô)       tier   : cùng tầng
     |   column    : cùng cột                  shelf  : cùng kệ/tủ (mặc định)
     |   warehouse : cùng kho
     | Vị trí chưa gắn cấp đó (cột null) thì chỉ xét trong chính vị trí.
     */
    'incompatibility_scope' => 'shelf',

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
        // Nhãn chỉ in số biểu mẫu, không in số SOP
        'form_no' => 'QC/F/106-03',
        'width_mm' => 60,
        'height_mm' => 40,
        // Cạnh phần MÃ QR (mm, chưa tính vùng trắng) ở góc dưới bên phải nhãn, giữa có logo
        // Stella - cùng kiểu nhãn vật tư nhưng nhỏ hơn 25% (vật tư 16mm).
        'qr_size_mm' => 12,
        // Độ phân giải đầu in Zebra (ZD421: bản 203 hoặc 300 dpi - xem tem dưới đáy máy).
        'dpi' => 300,
    ],
];
