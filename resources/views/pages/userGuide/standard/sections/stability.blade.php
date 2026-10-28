<p>Chất chuẩn để lâu thì hàm lượng thay đổi. Tab <b>Đánh giá hạn dùng CTC</b> trên màn hình <b>Tồn Kho › Chất Chuẩn</b> lập
    <b>phiếu đánh giá hạn dùng</b> cho từng ống <b>chuẩn thứ cấp (CTC)</b> để theo dõi độ ổn định qua các <b>mốc</b> (T0, T3, T6...).</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-gia.png', 'caption' => 'Màn hình Tồn Kho › Chất Chuẩn › tab Đánh giá hạn dùng CTC'])

<h3>Lập phiếu đánh giá</h3>
<ol class="ug-steps">
    <li>Bấm <b>Lập phiếu đánh giá</b> trên tab, hoặc bấm nhãn <span class="ug-chip">+ ĐGHD</span> dưới cột Hạn Dùng của ống ở tab
        <b>Theo mã ống chuẩn</b> (modal mở sẵn đúng ống đó).</li>
    <li>Chọn ống chuẩn. Chỉ ống CTC được tick <b>Lô đánh giá hạn dùng</b> lúc nhập mới hiện ở đây. Mỗi ống chỉ có <b>một</b> phiếu còn
        hiệu lực; muốn lập lại thì huỷ phiếu cũ trước.</li>
    <li>Nhập <b>ngày bắt đầu</b> và <b>chu kỳ đánh giá</b> (số tháng).</li>
    <li>Khai các mốc: mỗi mốc có số tháng tính từ ngày bắt đầu (ngày đến hạn tự tính) và các <b>chỉ tiêu</b> cần thử. Thêm, sửa, xoá mốc được.</li>
</ol>

<h3>Theo dõi ngay trên bảng tồn</h3>
<p>Ống đã có phiếu còn hiệu lực hiện nhãn <span class="ug-chip">ĐGHD 2/5</span> (số mốc đã đánh giá / tổng số mốc) dưới cột
    <b>Hạn Dùng</b> của tab Theo mã ống chuẩn. Nhãn vàng là có mốc quá hạn, đỏ là phiếu đã dừng đánh giá. Bấm nhãn để mở trang chi tiết phiếu.</p>

<h3>Thực hiện tại mỗi mốc</h3>
<ul>
    <li>Đánh dấu <b>đã cấp phát chuẩn</b> cho từng chỉ tiêu, kèm ghi chú riêng, vì chuẩn thường cấp làm nhiều lần.</li>
    <li>Ghi ngày làm, kết quả và kết luận <b>Đạt / Không Đạt</b>.</li>
    <li>Mốc <b>Không Đạt</b>: phiếu tự <b>dừng đánh giá</b>, hệ thống ghi lý do.</li>
    <li>Mốc <b>Đạt</b>: chọn đánh giá tiếp, hoặc ngưng vì lý do khác (dùng hết ống, đổi chuẩn...); ngưng thì phải nhập lý do. Phiếu đã ngưng
        khoá mọi thao tác ghi cho tới khi bấm <b>Đánh giá tiếp</b>.</li>
</ul>

<h3>Trạng thái phiếu</h3>
<p>Tự chạy theo các mốc: <span class="ug-chip">Ban Đầu</span> <span class="ug-chip">Đang Đánh Giá</span>
    <span class="ug-chip is-success">Hoàn Thành</span> <span class="ug-chip is-warn">Dừng Đánh Giá</span>
    <span class="ug-chip is-danger">Huỷ</span>. Phiếu huỷ không bị xoá. Mọi thay đổi đều ghi lịch sử, xem ngay ở trang chi tiết.
    Số trên nút tab là số phiếu còn đang theo dõi (chưa Hoàn Thành / Dừng / Huỷ).</p>

<h3>Dữ liệu chuyển từ SMS 2.0</h3>
<p class="mb-0">Phiếu đánh giá (bảng DOD) của QC1, QC2 trên SMS 2.0 đã được chuyển sang, người lập ghi <b>Import SMS 2.0 (system)</b>.
    SMS 2.0 chỉ lưu các mốc từ lúc bắt đầu dùng phần mềm nên phiếu cũ có thể bắt đầu từ mốc 9, 12... tháng; ngày thực hiện, kết quả
    và ghi chú cấp phát gốc nằm ở cột Ghi chú của mốc. Phiếu của ống đã dùng hết (không còn tồn) không chuyển sang.</p>
