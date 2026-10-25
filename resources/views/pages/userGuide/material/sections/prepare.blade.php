<p>Nơi <b>kho</b> làm việc: liệt kê các dòng đề nghị đã duyệt (nội bộ, và liên phòng ban gửi tới phòng mình) còn phải cấp.
    Cấp phát là <b>xuất kho</b>: tồn bị trừ ngay và hệ thống tự ghi vào <a href="#so-cap-phat">Sổ cấp phát</a>.</p>

<h3>Cấp phát</h3>
<ol class="ug-steps">
    <li>Xem cột <b>Cần Soạn</b> và <b>Tồn Khả Dụng</b>. Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-print mr-1"></i> In phiếu soạn hàng</span>
        nếu cần đi lấy hàng theo danh sách.</li>
    <li>Cột <b>Cấp Phát</b> đã điền sẵn lô và số lượng theo FEFO/FIFO. Bấm nút bên cạnh để xem danh mục tồn của vật tư
        và chọn lô khác (nếu có quyền).</li>
    <li>Bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-check mr-1"></i>Cấp Phát</span>.</li>
</ol>

<h3>Quy tắc khi cấp</h3>
<ul>
    <li>Không có quyền <b>Điều Chỉnh Mã Xuất Nhập Khi Cấp Phát</b>: chỉ cấp được từ đúng các lô hệ thống đề xuất.</li>
    <li>Không có quyền <b>Điều Chỉnh Số Lượng Khi Cấp Phát</b>: phải cấp đúng số đề xuất.</li>
    <li>Mỗi lô được cấp vượt tối đa <b>5%</b> so với phần còn hứa được (bù sai số cân, đong).</li>
    <li>Không cấp được lô <b>đã hết hạn</b>, lô của phòng khác, hoặc phần đang giữ cho việc khác (cách ly, hiệu chuẩn).</li>
    <li>Kho thiếu hàng thì cấp được bao nhiêu hay bấy nhiêu: dòng chuyển sang <b>Cấp phát một phần</b> và bấm
        <b>Cấp phát thêm</b> khi có hàng. Lọc <i>Thiếu hàng</i> để xem các dòng kho không đủ.</li>
    <li>Tồn chia lần lượt theo thứ tự phiếu: phiếu lập trước được giữ tồn trước.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-ruler-combined"></i>
    <div>Vật tư <b>Cần hiệu chuẩn trước khi dùng</b> không cấp ngay được. Bấm <b>Đề nghị HC</b> để gửi QA hiệu chuẩn trước.
        Xem <a href="#hieu-chuan">Hiệu Chuẩn Trước Khi Cấp</a>.</div>
</div>
