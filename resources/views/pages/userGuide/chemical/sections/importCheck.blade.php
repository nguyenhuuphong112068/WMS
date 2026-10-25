<p>Tab đầu tiên của màn Nhập: tạo phiếu nhập và kiểm tra các lô vừa nhập. Lô mới luôn ở trạng thái
    <span class="badge badge-warning">Chờ kiểm tra</span>, nằm trong khu Biệt Trữ và <b>chưa cộng vào tồn</b>.</p>

<h3>Tạo phiếu nhập</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Nhập hoá chất</span>.</li>
    <li>Chọn <b>Hoá Chất</b> (chỉ hiện hoá chất phòng đã khai ở tab Hoá chất của phòng), nhập <b>Số Lượng</b> theo đơn vị của phòng.
        Muốn tách thành nhiều lô cùng thông tin thì tăng <b>Số Lần Nhập</b> (tối đa 50), mỗi lô một mã.</li>
    <li>Điền Số Lô, Nhà Cung Cấp, Hạn Sử Dụng, Số / Ngày Hoá Đơn, chọn <b>Định Khu Tạm</b> trong khu Biệt Trữ, đính kèm hồ sơ
        (tối đa 10MB mỗi file).</li>
    <li>Bấm <b>Lưu</b>. Ngày Nhập và Người Nhập do hệ thống tự ghi, không sửa được.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Với hoá chất thuộc diện <b>ngưỡng tồn trữ PL IV</b>, ngay khi chọn hoá chất và gõ số lượng, form báo trước tồn trữ toàn công ty
        sau khi nhập so với ngưỡng. Đây là cảnh báo để cân nhắc, hãy xem <a href="#nguong-pl-iv">Ngưỡng Tồn Trữ PL IV</a>.
        <b>Hạn Sử Dụng</b> không được trước ngày nhập.</div>
</div>

<h3>Xác nhận kiểm tra</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-clipboard-check mr-1"></i> Kiểm tra</span> trên lô đang chờ.</li>
    <li><b>Đạt</b>: bắt buộc chọn <b>Định khu vị trí lưu trữ thật</b>; bổ sung số lô, nhà cung cấp, hoá đơn, hạn sử dụng nếu còn
        thiếu. Lô được cộng vào tồn và dùng được ngay.</li>
    <li><b>Không đạt</b>: bắt buộc ghi <b>Kết luận kiểm tra</b>. Lô coi như <b>trả hàng</b>, không bao giờ vào tồn.</li>
</ol>
<p class="mb-0">Kết quả kiểm tra là quyết định cuối, không kiểm tra lại được. Hoá chất nhận từ phòng khác không qua bước này mà kiểm tra
    ngay lúc <a href="#lien-phong-ban">Nhận hàng</a>.</p>
