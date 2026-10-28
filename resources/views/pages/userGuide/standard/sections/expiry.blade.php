<h3>Tab "Cận hạn dùng"</h3>
<p>Liệt kê các ống có <b>hạn áp dụng</b> (hạn nội bộ nếu có, không thì hạn nhà sản xuất) rơi vào trong <b>6 tháng</b> tới, kể cả ống đã quá hạn;
    ống còn dưới <b>30 ngày</b> được đánh dấu sắp hết hạn. Lọc nhanh theo mốc
    <span class="ug-key">≤ 3 tháng</span> <span class="ug-key">≤ 1 tháng</span> <span class="ug-key">≤ 2 tuần</span>
    <span class="ug-key">≤ 1 tuần</span> (mỗi mốc kèm số ống). Từ đây có thể bấm
    <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-biohazard"></i></span> để <a href="#cach-ly">cách ly</a> ống đã hoặc sắp hết hạn.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/han-dung.png', 'caption' => 'Màn hình Tồn Kho › Chất Chuẩn › tab Cận hạn dùng / Chưa có hạn nội bộ'])

<h3>Tab "Chưa có hạn nội bộ"</h3>
<p>Ống của chuẩn có hạn dùng mặc định nhưng <b>chưa xác định hạn nội bộ</b>. Các ống này chưa cấp phát và chưa dùng được. Bấm
    <b>Xác định hạn dùng nội bộ</b>, chọn ngày mở ống; hệ thống tính <b>Hạn Dùng Nội Bộ Sau Khi Xác Định</b> = ngày mở ống + số tháng hạn dùng,
    không vượt hạn nhà sản xuất.</p>

<h3>Cập nhật hạn retest / check online</h3>
<p>Ống có loại hạn <b>Cần Retest định kỳ</b> hoặc <b>Check online</b> hiện badge trên bảng tồn. Sau mỗi lần retest hoặc tra cứu trực tuyến,
    bấm badge và chọn một trong ba hướng:</p>
<ul>
    <li><b>Tiếp tục Retest định kỳ</b>: nhập <b>hạn retest mới</b> (và chu kỳ retest nếu đổi).</li>
    <li><b>Tiếp tục Check online</b>: hạn dùng để trống.</li>
    <li><b>Hạn dùng đã xác định</b>: chốt một hạn cố định; ống chuyển hẳn sang loại hạn xác định và hết badge.</li>
</ul>
<p class="mb-0">Mỗi lần cập nhật lưu giá trị cũ → mới, ghi chú và file đính kèm (kết quả retest, ảnh tra cứu). Cần quyền
    <b>Cập Nhật Hạn Dùng Chất Chuẩn</b>.</p>
