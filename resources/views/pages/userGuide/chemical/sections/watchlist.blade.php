<p>Các hoá chất của phòng đang có tồn <b>dưới Ngưỡng Tồn Tối Thiểu</b> đã khai ở <a href="#hoa-chat-cua-phong">Hoá Chất Của Phòng</a>.
    Tồn tính đúng như màn Tồn Kho. Khác vật tư, danh sách hoá chất chỉ có nguồn này.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/can-du-tru.png', 'caption' => 'Màn hình Mua Hàng › Hoá Chất › tab Danh sách hoá chất cần mua hàng'])

<ol class="ug-steps">
    <li>Tick chọn các hoá chất cần mua.</li>
    <li>Khai số lượng và tháng cần dùng cho từng dòng.</li>
    <li>Lưu. Hệ thống tạo phiếu mua hàng <b>Nháp</b> và mở trang chi tiết để bổ sung rồi <a href="#du-tru">trình ký</a>.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Hoá chất đã nằm trong một phiếu mua hàng <b>chưa xong</b> (kể cả Nháp, Chờ ký, Bị từ chối) được gắn mã phiếu đó và khoá chọn lại,
        tránh mua hàng trùng.</div>
</div>
