@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])

<p>Nhật ký mọi lần vật tư rời kho của phòng: <b>Sử dụng</b> (cấp phát nội bộ), <b>Loại bỏ</b> (từ quy trình cách ly) và cấp
    phát liên phòng ban. Mỗi dòng ghi mã xuất nhập, vật tư, số lượng, mã phiếu đề nghị, mục đích, người thực hiện và thời gian.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/so-cap-phat.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Sổ cấp phát'])

<p>Sổ được ghi <b>tự động</b> khi kho cấp phát. Không có chỗ lập phiếu sử dụng bằng tay, để tránh trừ tồn hai lần.</p>

<h3>In nhãn cấp phát</h3>
<p>Ở cột Thao Tác của dòng loại <b>Sử dụng</b>, nút <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-qrcode"></i></span>
    mở trang in <b>nhãn cấp phát</b> để dán lên vật tư đã giao cho bộ phận dùng (chọn được số lượng nhãn cần in). Nhãn cùng khổ
    với nhãn nhập vật tư, gồm lưới 2 × 3: Người / Ngày Đề Nghị, Người / Ngày Cấp Phát, Số Lượng, Hạn Dùng, kèm Mục Đích và
    <b>mã QR</b> là mã xuất nhập (quét được như nhãn nhập). Mỗi lần in ghi Audit Trail <i>In nhãn cấp phát</i>.</p>
<ul>
    <li>Dòng của phiếu <b>cách ly</b> cũng có nút này: nhãn <b>CÁCH LY - CHỜ QUYẾT ĐỊNH</b> hoặc <b>LOẠI BỎ - CHỜ HUỶ</b> (dải đen
        + mã phiếu CL, người / ngày cách ly hoặc quyết định, vị trí, lý do) để dán lên hàng đang chờ xử lý.</li>
    <li>Cần quyền <b>In Nhãn Cấp Phát Vật Tư</b> (cấp sẵn cho Admin và các vai trò có quyền cấp phát); thiếu quyền thì nút mờ.</li>
</ul>

<h3>Điều chỉnh</h3>
<ul>
    <li>Người có quyền <b>Điều Chỉnh Sổ Sử Dụng Vật Tư</b> bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>
        để sửa hoặc khoá một dòng ghi sai. Mọi lần điều chỉnh đều lưu lịch sử - số nhỏ ở góc nút Sửa là số lần đã điều chỉnh, bấm vào để mở bảng <b>Lịch Sử Điều Chỉnh</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng sổ; ô đã đổi so với lần trước được tô vàng.</li>
    <li>Dòng <b>sinh từ phiếu cách ly</b> chỉ xử lý ở tab <a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>.</li>
    <li>Dòng <b>cấp phát liên phòng ban</b> không sửa được, vì số lượng đã thành tồn của phòng nhận.</li>
</ul>
