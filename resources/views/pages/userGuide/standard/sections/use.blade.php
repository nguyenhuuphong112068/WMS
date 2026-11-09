<p>Sổ ghi lại mọi phiếu sử dụng chất chuẩn của phòng, lọc theo ngày sử dụng, tìm theo mã ống / tên chuẩn / số lô / chỉ tiêu,
    quét mã vạch ống để tìm nhanh. Phiếu sử dụng mới lập ở tab <a href="#su-dung-chuan">Sử dụng chuẩn</a>, không lập ở tab này.
    Ống hỏng / hết hạn không ghi ở đây mà đi quy trình <a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/so-su-dung.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Chất Chuẩn › tab Sổ sử dụng chất chuẩn'])

<h3>Sửa một dòng</h3>
<p>Người có quyền <b>Điều Chỉnh Sổ Sử Dụng Chất Chuẩn</b> bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>
    để sửa một dòng ghi sai, kể cả phiếu của <b>ngày cũ</b>. Mọi lần điều chỉnh đều lưu lịch sử - số nhỏ ở góc nút Sửa
    là số lần đã điều chỉnh, bấm vào để mở bảng <b>Lịch Sử Điều Chỉnh</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng sổ; ô đã đổi so với lần trước được tô vàng.</p>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tổ Sử Dụng', true, ''],
    ['Ống Chuẩn', true, 'Đổi sang ống khác thì ống mới phải còn hạn, còn tồn và đã có hạn dùng nội bộ (nếu cần).'],
    ['Số Lượng', true, ''],
    ['Loại Phiếu', false, 'Chỉ hiển thị, không đổi được.'],
    ['Tên Sản Phẩm / Số Lô / Chỉ Tiêu', false, ''],
    ['Lý Do Điều Chỉnh', false, 'Không lưu trên phiếu, chỉ ghi vào lịch sử để giải thích lần sửa.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Dòng <b>Loại bỏ</b> trong sổ sinh từ phiếu cách ly (kèm trạng thái phiếu), chỉ điều chỉnh ở tab Cách ly - Loại bỏ - Huỷ. Mọi thay đổi
        của phiếu đều lưu lịch sử. Ống có nhãn
        <b>trả lại chuẩn</b> thì dùng xong phải trả về kho.</div>
</div>
