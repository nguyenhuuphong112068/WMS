<h3>Tab "Cận hạn dùng"</h3>
<p>Liệt kê các lô có <b>hạn áp dụng</b> rơi vào trong <b>6 tháng</b> tới, kể cả lô đã quá hạn. Lọc nhanh theo mốc
    <span class="ug-key">≤ 3 tháng</span> <span class="ug-key">≤ 1 tháng</span> <span class="ug-key">≤ 2 tuần</span>
    <span class="ug-key">≤ 1 tuần</span>. Từ đây có thể bấm <b>Cách ly</b> lô đã hoặc sắp hết hạn để đưa vào <a href="#cach-ly">quy trình huỷ</a>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/han-dung.png', 'caption' => 'Màn hình Tồn Kho › Hoá Chất › tab Cận hạn dùng / Chưa có hạn nội bộ'])

<h3>Tab "Chưa có hạn nội bộ"</h3>
<p>Các lô của hoá chất có <b>hạn dùng mặc định</b> (khai ở danh mục hoặc Hoá Chất Của Phòng) nhưng <b>chưa xác định hạn dùng nội bộ</b>.
    Các lô này <b>chưa được sử dụng</b> và chỉ chuyển cho phòng khác được nguyên cả lô.</p>

<h3>Xác định hạn dùng nội bộ</h3>
<ol class="ug-steps">
    <li>Bấm nút <b>Xác định hạn dùng nội bộ</b> trên lô.</li>
    <li>Chọn <b>Ngày Xác Định</b>, thường là ngày mở nắp hoặc ngày đưa vào sử dụng.</li>
    <li>Form hiện ngay <b>Hạn Dùng Nội Bộ Sau Khi Xác Định</b> = ngày xác định + số tháng hạn dùng mặc định. Nếu kết quả dài hơn
        <b>Hạn Nhà Sản Xuất</b> thì lấy hạn nhà sản xuất. Lưu.</li>
</ol>
<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Xác định lại được nhiều lần (ví dụ chọn nhầm ngày), mỗi lần đều ghi Audit Trail kèm giá trị cũ. Cần quyền
        <b>Đặt Hạn Dùng Nội Bộ Hoá Chất</b>.</div>
</div>
