<p>Tồn được <b>tính tự động</b> từ phiếu nhập, cân đối và các lần xuất. Không có chỗ nhập tồn bằng tay. Chọn
    <b>Từ ngày - Đến ngày</b> (mặc định trọn tháng hiện tại) để xem theo kỳ.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/ton-kho.png', 'caption' => 'Màn hình Tồn Kho › Vật Tư › tab Theo mã xuất nhập / Tồn theo mã vật tư / Tồn kho theo vị trí'])

<h3>Ba tab xem tồn</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 190px">Tab</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Theo mã xuất nhập</td>
                <td style="font-weight: 400">Từng lô: <b>Tồn Đầu Kỳ</b>, <b>Nhập Trong Kỳ</b>, <b>Sử Dụng</b>, <b>Cách Ly/Huỷ</b>,
                    <b>Tồn Cuối Kỳ</b>, hạn dùng, vị trí. Chỉ hiện lô còn tồn hoặc có phát sinh trong kỳ. Có nút Cân đối và Biểu đồ.</td>
            </tr>
            <tr>
                <td>Tồn theo mã vật tư</td>
                <td style="font-weight: 400">Gộp các lô theo vật tư: <b>Tổng Tồn Vật Tư</b>, hạn gần nhất, nhà sản xuất. Lọc được theo
                    phân loại vật tư.</td>
            </tr>
            <tr>
                <td>Tồn kho theo vị trí</td>
                <td style="font-weight: 400">Sơ đồ kho, kệ, vị trí giống <b>Định Khu</b>; bấm vào vị trí để xem đang có lô nào. Tìm
                    nhanh theo vị trí, kệ/tủ hoặc tên vật tư.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Cân đối số lượng</h3>
<p>Dùng khi tồn sổ sách của một lô lệch so với thực tế (sai số cân, hao hụt, đếm nhầm lúc nhập).</p>
<ol class="ug-steps">
    <li>Ở tab Theo mã xuất nhập, bấm nút <b>Cân đối</b> trên dòng.</li>
    <li>Nhập <b>Số Lượng Cân Đối</b> là <b>số điều chỉnh</b> (+ hoặc −), ví dụ <span class="ug-key">0.5</span> hay
        <span class="ug-key">-0.5</span>, không phải số tồn mới. Chọn <b>Thời Điểm Cân Đối</b> (mặc định là lúc mở form), ghi chú.</li>
    <li>Lưu. Bấm nút lịch sử trên dòng để xem các lần cân đối trước.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Tổng mọi lần cân đối của một lô <b>không vượt quá 5% lượng nhập</b>, và cân đối xong tồn <b>không được âm</b>. Lệch nhiều
        hơn thì cần làm rõ nguyên nhân, không xử lý bằng cân đối.</div>
</div>

<p class="mb-0"><b>Biểu đồ</b>: xem diễn biến nhập - xuất - tồn của một vật tư theo thời gian.</p>
