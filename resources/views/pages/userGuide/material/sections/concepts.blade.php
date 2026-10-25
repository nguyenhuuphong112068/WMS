<h3>Mã xuất nhập (mã lô)</h3>
<p>Mỗi lần nhập, hệ thống cấp cho mỗi lô một <b>mã xuất nhập</b> riêng, ví dụ <span class="ug-key">M-07-7KPMR9J4WD</span>.
    Mọi thao tác sau đó (cấp phát, cách ly, kiểm kê, cân đối) đều chỉ định đúng mã này. Mã được in thành <b>mã QR</b>
    trên nhãn dán lô; ở các màn hình có ô quét, chỉ cần quét QR là hệ thống nhảy tới đúng lô.</p>

<h3>Tồn và tồn khả dụng</h3>
<ul>
    <li><b>Tồn</b> = lượng nhập + cân đối − đã cấp phát − đã loại bỏ − đã chuyển cho phòng khác. Hệ thống tự tính, không
        nhập tay.</li>
    <li><b>Tồn khả dụng</b> (còn hứa được) = tồn trừ đi phần đang <b>giữ chỗ</b>: đề nghị đã lập nhưng kho chưa cấp, hàng
        đang cách ly, hàng đang gửi hiệu chuẩn. Đây là con số thật sự còn đem cấp được.</li>
    <li>Lô <b>Chờ kiểm tra</b>, lô <b>Không đạt</b> và lô <b>đã hết hạn</b> không được tính vào phần cấp phát được.</li>
</ul>

<h3>FEFO / FIFO</h3>
<p>Khi cấp phát, hệ thống đề xuất lô theo nguyên tắc <b>hết hạn trước - xuất trước</b> (FEFO). Lô không có hạn dùng thì
    theo <b>nhập trước - xuất trước</b> (FIFO). Một dòng đề nghị có thể được cấp từ nhiều lô nếu lô đầu không đủ.</p>

<h3>Ký điện tử bằng mật khẩu</h3>
<p>Các thao tác <span class="ug-chip is-warn">Ký bằng mật khẩu</span> (ký duyệt, từ chối, quyết định loại bỏ, xác nhận
    hiệu chuẩn...) bắt buộc <b>nhập lại mật khẩu đăng nhập</b> ngay trong hộp xác nhận. Đây là chữ ký điện tử theo
    21 CFR Part 11: chỉ đúng người được chỉ định mới ký được, không ký thay được.</p>

<h3>Màn hình nhiều tab</h3>
<p>Mỗi màn hình vật tư chia thành nhiều tab. Bạn chỉ thấy những tab được cấp quyền. Số nhỏ bên cạnh tên tab là số việc
    đang chờ xử lý trong tab đó.</p>

<h3>Ngày thao tác do hệ thống ghi</h3>
<p class="mb-0">Ngày nhập, ngày cấp phát, ngày ký... là thời điểm bạn bấm nút, hệ thống tự ghi và không sửa được. Các
    ngày thuộc chứng từ hoặc kế hoạch như <i>Hạn sử dụng</i>, <i>Ngày ký hoá đơn</i>, <i>Ngày mong muốn</i> thì nhập tay.</p>
