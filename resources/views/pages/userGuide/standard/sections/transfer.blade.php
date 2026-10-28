<p>Phòng <b>A</b> xin chất chuẩn từ kho của phòng <b>B</b>, theo đúng 3 bước như hoá chất và <b>không có bước ký</b>:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/lien-phong-ban.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Chất Chuẩn › tab Đề nghị cấp phát chuẩn liên phòng ban'])
<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: chọn phòng được đề nghị, thêm chuẩn (gõ mã, tên hoặc nhà sản xuất để tìm; đơn vị tự điền theo
        đơn vị phòng mình khai), số lượng, ngày mong muốn. Bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu</span> hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</span>.</li>
    <li><b>B cấp phát</b>: chọn ống trong kho của B và số lượng, hoặc từ chối từng mục. Tồn của B bị trừ ngay, mục chuyển sang chờ nhận.</li>
    <li><b>A nhận</b>: bước nhận cũng là bước kiểm tra; chọn định khu thật của A và có thể đánh dấu <b>chiết ống trước khi dùng</b> cho
        ống nhận về. Ống mới vào tồn của A ngay. Không đạt thì <b>Từ chối nhận</b>, tồn tự hoàn lại cho B.</li>
</ol>
<p>Hai khung <b>Đề nghị phòng mình đã gửi đi</b> và <b>Đề nghị phòng ban khác gửi đến</b> đều có hai tab con:
    <b>Chờ xử lý</b> (luôn hiện đủ, không lọc ngày: phiếu chưa cấp xong hoặc còn mục chờ nhận) và
    <b>Đã hoàn thành</b> (đã cấp đủ, từ chối hoặc huỷ, lọc theo ngày lập).</p>
<p class="mb-0">A phải khai chuẩn ở <a href="#chuan-cua-phong">Chất Chuẩn Của Phòng</a> trước khi nhận.</p>
