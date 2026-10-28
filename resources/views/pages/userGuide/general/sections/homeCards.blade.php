<p>Thẻ chia theo nhóm nghiệp vụ (cùng thứ tự với hàng của <a href="#ma-tran">ma trận</a>). Mỗi thẻ là <b>một loại việc của một
    đối tượng</b>, ví dụ "Ký dự trù" của Hoá chất, mang màu của đối tượng đó.</p>

<h3>Đọc một thẻ</h3>
<ol class="ug-steps">
    <li>Góc trên: chip đối tượng và nhãn <b>ai phải làm</b>:
        <span class="ug-chip"><i class="fas fa-signature"></i> Bạn ký duyệt</span> việc chờ chính bạn ký;
        <span class="ug-chip is-warn"><i class="fas fa-people-carry"></i> Phòng xử lý</span> việc chờ phòng ban đang chọn (ai trong phòng
        có quyền đều làm được);
        <span class="ug-chip is-success"><i class="fas fa-user-check"></i> Cấp cho bạn</span> thông báo riêng cho bạn, không cần ký.</li>
    <li>Tên loại việc và tổng số phiếu.</li>
    <li>Danh sách phiếu, chờ lâu nhất lên đầu: mã phiếu, thời gian đã chờ, tiêu đề và thông tin thêm. Với đề nghị giữa hai phòng có
        chip <b>← Từ</b> &lt;phòng gửi&gt; hoặc <b>→ Đến</b> &lt;phòng nhận&gt;. Thẻ "Bạn ký duyệt" và "Cấp cho bạn" hiện đủ mọi phiếu;
        thẻ "Phòng xử lý" hiện 5 phiếu đầu.</li>
    <li>Chân thẻ: <b>Xem tất cả ... đề nghị</b> hoặc <b>Mở màn hình xử lý</b> để mở màn hình gốc.</li>
</ol>

<h3>Mở và xử lý phiếu</h3>
<p>Bấm vào một phiếu để mở thẳng trang hoặc hộp thoại chi tiết của phiếu đó. Nút <b>Đồng ý</b> / <b>Từ chối</b> ký duyệt nằm trong
    chi tiết phiếu, không nằm trên dòng danh sách. Ký duyệt phải nhập lại mật khẩu đăng nhập.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Phiếu chờ bạn ký thuộc phòng ban khác phòng đang chọn (thường gặp với Ban Giám Đốc, Quản lý nhiều phòng): bấm vào phiếu,
        hệ thống <b>tự chuyển</b> phòng ban đang chọn sang phòng của phiếu rồi mới mở. Ký xong, nhớ kiểm tra lại ô phòng ban
        cuối cột icon trước khi làm việc khác.</div>
</div>

<h3>Các loại việc</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 200px">Nhóm</th>
                <th style="width: 260px">Loại việc</th>
                <th style="width: 140px">Ai làm</th>
            </tr>
        </thead>
        <tbody>
            <tr><td rowspan="3">Dự trù</td><td style="font-weight: 400">Ký dự trù</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Dự trù mới chờ tiếp nhận</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td style="font-weight: 400">Đề nghị huỷ mục dự trù</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td rowspan="3">Đề nghị nội bộ</td><td style="font-weight: 400">Ký đề nghị cấp phát</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Chờ kho cấp</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td style="font-weight: 400">Chuẩn đã cấp cho bạn</td><td style="font-weight: 400">Cấp cho bạn</td></tr>
            <tr><td rowspan="3">Liên phòng ban</td><td style="font-weight: 400">Ký đề nghị liên phòng ban</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Chờ phòng bạn cấp phát</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td style="font-weight: 400">Hàng đã cấp, chờ bạn nhận</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td rowspan="3">Văn phòng phẩm</td><td style="font-weight: 400">Ký đề nghị VPP</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Chờ phòng bạn cấp VPP</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td style="font-weight: 400">VPP đã cấp, chờ bạn nhận</td><td style="font-weight: 400">Phòng xử lý</td></tr>
            <tr><td rowspan="4">KS chất lượng / hàng hỏng</td><td style="font-weight: 400">Chờ QA hiệu chuẩn</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Vật tư hỏng chờ quyết định</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Hoá chất hỏng chờ quyết định</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Chất chuẩn hỏng chờ quyết định</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td rowspan="2">Phê duyệt dữ liệu</td><td style="font-weight: 400">Danh mục chờ duyệt</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
            <tr><td style="font-weight: 400">Dữ liệu gốc chờ duyệt</td><td style="font-weight: 400">Bạn ký duyệt</td></tr>
        </tbody>
    </table>
</div>

<p>Bạn chỉ thấy loại việc mà vai trò của bạn được giao (ví dụ người ký dự trù, QA, thủ kho). Thiếu một loại việc mong đợi thì xem
    <a href="#loi-thuong-gap">Lỗi Thường Gặp</a>.</p>
