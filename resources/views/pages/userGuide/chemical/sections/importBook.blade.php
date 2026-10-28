<p>Toàn bộ các lô đã nhập của phòng (kể cả lô nhận chuyển từ phòng khác), kèm tình trạng kiểm tra:
    <span class="badge badge-warning">Chờ kiểm tra</span> <span class="badge badge-success">Kiểm tra Đạt</span>
    <span class="badge badge-danger">Không đạt</span>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/so-nhap.png', 'caption' => 'Màn hình Nhập › Hoá Chất › tab Sổ nhập hoá chất'])

<h3>Các nút trên dòng</h3>
<ul>
    <li><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> <b>Điều chỉnh</b>: sửa thông tin phiếu; lô chưa
        kiểm tra vẫn chỉ đặt được trong khu Biệt Trữ. Số nhỏ ở góc nút là số lần đã điều chỉnh, bấm vào để xem lịch sử.</li>
    <li><span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-tag"></i></span> <b>In nhãn</b>: nhãn 60 × 40 mm cho máy
        in Zebra, có mã QR, các cảnh báo an toàn và số biểu mẫu. Mỗi lần in được ghi vào Audit Trail.</li>
    <li><i class="fas fa-paperclip"></i> <b>File đính kèm</b>: xem, thêm, hoặc đổi trạng thái hiệu lực của file hồ sơ.</li>
</ul>
