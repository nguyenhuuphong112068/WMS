@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])

<p>Nhật ký mọi lần vật tư rời kho của phòng: <b>Sử dụng</b> (cấp phát nội bộ), <b>Loại bỏ</b> (từ quy trình cách ly) và cấp
    phát liên phòng ban. Mỗi dòng ghi mã xuất nhập, vật tư, số lượng, mã phiếu đề nghị, mục đích, người thực hiện và thời gian.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/so-cap-phat.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Sổ cấp phát'])

<p>Sổ được ghi <b>tự động</b> khi kho cấp phát. Không có chỗ lập phiếu sử dụng bằng tay, để tránh trừ tồn hai lần.</p>

<h3>Điều chỉnh</h3>
<ul>
    <li>Người có quyền <b>Điều Chỉnh Sổ Sử Dụng Vật Tư</b> bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>
        để sửa hoặc khoá một dòng ghi sai. Mọi lần điều chỉnh đều lưu lịch sử.</li>
    <li>Dòng <b>sinh từ phiếu cách ly</b> chỉ xử lý ở tab <a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>.</li>
    <li>Dòng <b>cấp phát liên phòng ban</b> không sửa được, vì số lượng đã thành tồn của phòng nhận.</li>
</ul>
