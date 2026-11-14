<p>Nhật ký mọi lần VPP rời kho của phòng: <b>Sử dụng</b> (ghi ở tab <a href="#su-dung-vpp">Sử Dụng VPP</a>, và các phiếu cấp
    phát theo đề nghị cũ), <b>Loại bỏ</b> (từ quy trình cách ly) và cấp phát liên phòng ban. Mỗi dòng ghi mã xuất nhập, mã phiếu
    đề nghị (nếu có), VPP, số lượng, loại, thời gian, mục đích và <b>Người Sử Dụng</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/so-cap-phat.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › VPP › tab Sổ sử dụng VPP'])

<p>Lọc theo khoảng <b>Ngày sử dụng</b> và tìm theo mã xuất nhập, mã phiếu đề nghị, tên VPP, người sử dụng, mục đích.</p>

<h3>Điều chỉnh</h3>
<ul>
    <li>Người có quyền <b>Điều Chỉnh Sổ Sử Dụng VPP</b> bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>
        để mở hộp <b>Điều chỉnh phiếu sử dụng VPP</b>: sửa số lượng, <b>Mục đích</b> riêng của phiếu đó, ghi lý do điều chỉnh;
        hoặc khoá dòng ghi sai. Mọi lần điều chỉnh đều lưu lịch sử - số nhỏ ở góc nút Sửa là số lần đã điều chỉnh, bấm vào để mở bảng <b>Lịch Sử Điều Chỉnh</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng sổ; ô đã đổi so với lần trước được tô vàng.</li>
    <li>Dòng <b>sinh từ phiếu cách ly</b> (biểu tượng <i class="fas fa-lock"></i>) chỉ xử lý ở tab <a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>.</li>
    <li>Dòng <b>cấp phát liên phòng ban</b> không sửa được, vì số lượng đã thành tồn của phòng nhận.</li>
</ul>
