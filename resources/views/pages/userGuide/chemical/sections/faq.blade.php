<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 38%">Hiện tượng</th>
                <th>Nguyên nhân và cách xử lý</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>"Phòng đã có gói cho sản phẩm ... với chỉ tiêu này"</td>
                <td style="font-weight: 400">Mỗi cặp Sản phẩm + Chỉ tiêu chỉ có một gói đang dùng trong phòng. Sửa gói đã có ở
                    <a href="#theo-san-pham">Danh sách hoá chất theo sản phẩm</a>, hoặc khoá gói cũ rồi tạo gói mới.</td>
            </tr>
            <tr>
                <td>"Có hoá chất chưa khai (hoặc đã khoá) ở tab Hoá chất của phòng" khi lưu gói</td>
                <td style="font-weight: 400">Gói chỉ gồm hoá chất phòng đang dùng. Dòng màu đỏ trong bảng gói là hoá chất đã bị khoá ở
                    <a href="#hoa-chat-cua-phong">Hoá Chất Của Phòng</a>: bỏ dòng đó hoặc mở khoá lại hoá chất.</td>
            </tr>
            <tr>
                <td>Chọn gói ở Cách 3 nhưng báo đỏ "Không còn lô dùng được: ..."</td>
                <td style="font-weight: 400">Các hoá chất đó không còn lô nào còn tồn, còn hạn, đã kiểm tra Đạt và đã có hạn dùng nội bộ.
                    Kiểm tra ở <a href="#ton-kho">Tồn kho</a>, xác định <a href="#han-dung">hạn nội bộ</a> hoặc lập <a href="#du-tru">dự trù</a>.</td>
            </tr>
            <tr>
                <td>Lô hệ thống gợi ý theo gói không đủ số lượng</td>
                <td style="font-weight: 400">Lô được chọn theo FEFO rồi FIFO, không xét lượng. Bấm <b>Đổi lô</b> trên dòng để chọn mã xuất
                    nhập khác, hoặc thêm lô thứ hai bằng Cách 1 / Cách 2.</td>
            </tr>
            <tr>
                <td>Không tìm thấy hoá chất khi nhập kho</td>
                <td style="font-weight: 400">Chưa khai ở <a href="#hoa-chat-cua-phong">Hoá Chất Của Phòng</a>, hoặc danh mục công ty chưa được duyệt.</td>
            </tr>
            <tr>
                <td>Đã nhập mà tồn không tăng, không dùng được</td>
                <td style="font-weight: 400">Lô còn <b>Chờ kiểm tra</b>. Vào tab <a href="#nhap-hoa-chat">Nhập hoá chất</a> và kiểm tra Đạt.</td>
            </tr>
            <tr>
                <td>"Lô ... chưa xác định hạn dùng nội bộ"</td>
                <td style="font-weight: 400">Vào Tồn Kho › tab <a href="#han-dung">Chưa có hạn nội bộ</a>, xác định hạn theo ngày mở nắp.</td>
            </tr>
            <tr>
                <td>"Lô ... đã hết hạn sử dụng"</td>
                <td style="font-weight: 400">Không dùng được lô hết hạn. Đưa lô vào <a href="#cach-ly">Cách ly</a> để xử lý.</td>
            </tr>
            <tr>
                <td>Báo vượt hạn mức, có tên người đang giữ</td>
                <td style="font-weight: 400">Lượng dùng cộng phần đang nằm trong Phiếu Tạm của mọi người đã vượt tồn + 5%. Liên hệ người đang
                    giữ để lưu hoặc xoá phiếu tạm.</td>
            </tr>
            <tr>
                <td>Lưu định khu xong hiện "Cảnh báo tương kỵ"</td>
                <td style="font-weight: 400">Tầng đó đã có hoá chất <b>tương kỵ</b> (Sơ đồ lưu trữ GHS). Dữ liệu vẫn được lưu; nên chuyển
                    sang tầng khác. Xem các chỗ đang tương kỵ bằng chip <b>Tương kỵ</b> ở tab Theo định khu của Tồn Kho Hoá Chất.</td>
            </tr>
            <tr>
                <td>Không lưu được phiếu dùng hoá chất cấm</td>
                <td style="font-weight: 400">Thiếu <b>Người Kiểm Tra</b>. Chọn người có vai trò Quản Lý Kho Hoá Chất của phòng.</td>
            </tr>
            <tr>
                <td>"... không được cấp phát lẻ" khi chuyển liên phòng ban</td>
                <td style="font-weight: 400">Lô chưa xác định hạn nội bộ: chuyển nguyên cả lô, hoặc xác định hạn nội bộ trước.</td>
            </tr>
            <tr>
                <td>Không bấm Nhận được hàng chuyển đến</td>
                <td style="font-weight: 400">Phòng nhận chưa khai hoá chất này ở Hoá Chất Của Phòng.</td>
            </tr>
            <tr>
                <td>Cột kg để trống, báo "không quy đổi được"</td>
                <td style="font-weight: 400">Thiếu Tỉ Trọng (đơn vị thể tích) hoặc Khối Lượng Quy Đổi (đơn vị đếm). Bổ sung ở Danh Mục.</td>
            </tr>
            <tr>
                <td>Phân loại NĐ 24/2026 hiện sai</td>
                <td style="font-weight: 400">Phân loại tự suy từ Dữ Liệu Gốc. Kiểm tra hoạt chất thành phần, % khối lượng và nhóm nguy hại ở
                    <b>Dữ Liệu Gốc › Hoá Chất / Đơn Chất</b>.</td>
            </tr>
            <tr>
                <td>"Chỉ được cân đối tối đa 5% số lượng nhập"</td>
                <td style="font-weight: 400">Lệch quá lớn, không xử lý bằng cân đối. Làm rõ nguyên nhân (nhập sai số lượng thì Điều chỉnh phiếu nhập).</td>
            </tr>
        </tbody>
    </table>
</div>
