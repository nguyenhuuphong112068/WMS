<p>Đối chiếu khối lượng tồn trữ <b>toàn công ty</b> với ngưỡng của <b>Phụ lục IV Nghị định 24/2026/NĐ-CP</b>, để biết cơ sở có phải
    xây dựng <b>Kế hoạch phòng ngừa, ứng phó sự cố hoá chất</b> hay không. Nằm ở tab <b>Đối chiếu ngưỡng PL IV</b> của màn hình <b>Tồn Kho › Hoá Chất</b>; tab chỉ để xem, phạm vi cộng tồn là
    mọi phòng ban thuộc công ty của phòng đang chọn. Số liệu luôn tính đến hiện tại nên khi mở tab này ô <b>Kỳ báo cáo</b> và lọc
    <b>Hoá chất VS</b> được ẩn đi.</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 200px">Phần</th>
                <th>Cách tính</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nhóm 9 · Bảng A</td>
                <td style="font-weight: 400">Từng <b>hoạt chất</b>: tổng tồn quy ra kg × % hàm lượng hoạt chất, so với ngưỡng của hoạt chất
                    (khai ở Dữ Liệu Gốc › Hoạt Chất). Chất thuộc <b>mục gộp</b> được cộng chung vào ngưỡng của mục gộp.</td>
            </tr>
            <tr>
                <td>Nhóm 10 · Bảng B</td>
                <td style="font-weight: 400">Từng <b>hỗn hợp</b>: tổng tồn thô quy ra kg (<b>không</b> nhân %), so với <b>ngưỡng thấp nhất</b>
                    trong các nhóm nguy hại đã tick.</td>
            </tr>
            <tr>
                <td>Tổng tỉ lệ</td>
                <td style="font-weight: 400"><b>Σ (qxᵢ / QUXᵢ)</b> gộp cả nhóm 9 và nhóm 10. Từ <b>1</b> trở lên thì cơ sở phải xây dựng
                    Kế hoạch phòng ngừa, ứng phó sự cố hoá chất.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Đọc kết quả</h3>
<ul>
    <li><b>Tồn Cao Nhất qxᵢ</b>: mức tồn quy ra kg <b>cao nhất đã từng đạt</b>, dựng lại từ chứng từ nhập - xuất - cân đối theo ngày.
        Trạng thái xét theo mức đỉnh này: đã từng chạm ngưỡng thì vẫn phải lập kế hoạch dù nay đã xuất bớt. Cột
        <b>Theo Tồn Hiện Tại</b> cho tham khảo tỉ lệ ở thời điểm hôm nay.</li>
    <li>Mức cảnh báo: <span class="ug-chip is-warn">Sắp chạm ngưỡng</span> khi đạt từ <b>{{ (int) round(config('chemical.threshold_iv.warn_ratio') * 100) }}%</b>,
        <span class="ug-chip is-danger">Vượt ngưỡng</span> khi từ 100%.</li>
    <li>Bấm vào con số để xem các mã xuất nhập tạo nên nó, hoặc diễn biến chứng từ tạo nên mức cao nhất. Cột <b>Phòng Ban Đóng Góp</b>
        cho biết lượng của từng phòng.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Số kg chỉ đúng khi mọi lô quy được ra kg: cần <b>Tỉ Trọng</b> (đơn vị thể tích), <b>Khối Lượng Quy Đổi</b> (đơn vị đếm) và
        <b>Hàm Lượng Hoạt Chất</b> khai đầy đủ ở Danh Mục. Cảnh báo cùng cách tính cũng hiện ngay khi nhập kho và lập dự trù.</div>
</div>
