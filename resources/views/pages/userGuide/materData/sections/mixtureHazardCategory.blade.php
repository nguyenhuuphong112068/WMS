<p>Các nhóm nguy hại theo <b>Phụ lục IV Bảng B</b> (Nghị định 24/2026/NĐ-CP), phân loại hỗn hợp theo GHS. Mỗi nhóm
    có một ngưỡng tồn trữ. Ở màn hình <a href="#hoa-chat">Hoá Chất</a>, người dùng tick các nhóm này cho từng hỗn hợp.
    Hệ thống đối chiếu tổng tồn của công ty với <b>ngưỡng thấp nhất</b> trong các nhóm đã tick.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/nhom-nguy-hai-bang-b.png', 'caption' => 'Màn hình Nhóm Nguy Hại Bảng B'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/nhom-nguy-hai-bang-b-form.png', 'caption' => 'Hộp thoại thêm mới - Nhóm Nguy Hại Bảng B'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phần Bảng B', true, '<b>I</b> Nguy hại sức khỏe · <b>II</b> Nguy hại vật chất · <b>III</b> Nguy hại cho môi trường · <b>IV</b> Nguy hại khác.'],
    ['Nhóm Phân Loại', true, 'Ghi đúng tên nhóm theo Bảng B. Không trùng.'],
    ['Ngưỡng Tồn Trữ (kg)', true, 'Khối lượng tồn trữ lớn nhất tại một thời điểm, lớn hơn 0.'],
    ['Cách Tính Ngưỡng', false, '<b>Mặc định</b>: tính theo tổng khối lượng. Chọn <b>Theo khối lượng tịnh (net)</b> cho các nhóm mà Bảng B ghi chú "(net)".'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><b>Mã nhóm</b> do hệ thống tự sinh (bắt đầu bằng chữ <span class="ug-key">B</span>). Chỉ nhóm <b>Đã duyệt</b> và
        đang hoạt động mới được tick ở màn hình Hoá Chất.</div>
</div>
