<p>Tồn được <b>tính tự động</b> từ phiếu nhập, cân đối và các lần xuất. Chọn <b>Từ ngày - Đến ngày</b> (mặc định trọn tháng hiện tại)
    để xem theo kỳ: Tồn Đầu Kỳ, Nhập / Cân Đối / Sử Dụng / Huỷ Trong Kỳ, Tồn Cuối Kỳ.</p>

<h3>Ba tab xem tồn</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 180px">Tab</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Theo mã xuất nhập</td>
                <td style="font-weight: 400">Từng lô: các chỉ số theo kỳ, hạn dùng, hạn nội bộ, vị trí thực tế. Trên dòng có nút
                    <b>Cân đối</b>, <b>Cách ly</b>, <b>Biểu đồ</b>, file đính kèm và xác định hạn nội bộ.</td>
            </tr>
            <tr>
                <td>Theo hoá chất</td>
                <td style="font-weight: 400">Gộp các lô theo hoá chất: tổng tồn, hạn gần nhất, tồn theo từng số lô.</td>
            </tr>
            <tr>
                <td>Theo định khu</td>
                <td style="font-weight: 400">Sơ đồ kho - kệ - vị trí, bấm vào vị trí để xem đang có lô nào.</td>
            </tr>
        </tbody>
    </table>
</div>
<p>Trạng thái tồn: <b>Còn hàng</b>, <b>Sắp hết</b> (dưới ngưỡng tối thiểu của phòng, hoặc dưới 20% lượng nhập nếu chưa đặt ngưỡng),
    <b>Hết hàng</b>, <b>Âm kho</b> (đã dùng vượt lượng nhập). Trạng thái hạn: <b>Sắp hết hạn</b> (còn dưới 30 ngày), <b>Hết hạn</b>. Ô lọc <b>Hoá chất VS</b> lọc theo thuộc tính hoá chất vi sinh của phiếu nhập, áp dụng cho mọi tab.</p>

<h3>Cân đối số lượng</h3>
<ol class="ug-steps">
    <li>Ở tab Theo mã xuất nhập, bấm <b>Cân đối</b> trên lô.</li>
    <li>Nhập <b>Số Lượng Cân Đối</b> là <b>số điều chỉnh</b> (+ hoặc −), không phải số tồn mới. Chọn <b>Thời Điểm Cân Đối</b>, ghi chú. Form
        hiện sẵn tổng đã cân đối và hạn mức còn lại.</li>
    <li>Lưu. Bấm nút lịch sử để xem các lần cân đối trước.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Tổng mọi lần cân đối của một lô <b>không vượt quá 5% lượng nhập</b>, và cân đối xong tồn <b>không được âm</b>. Cân đối luôn tính trên
        số liệu hôm nay, không phụ thuộc kỳ đang lọc.</div>
</div>
