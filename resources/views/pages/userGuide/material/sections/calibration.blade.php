@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])
@include('pages.userGuide.shared.flowMapLink', ['lane' => 'cach-ly', 'no' => 4, 'label' => 'Cách ly - Loại bỏ - Huỷ'])

<p>Dành cho vật tư có phân loại <b>Cần Hiệu Chuẩn Trước Khi Dùng = Có</b>. Chỉ phần đã được <b>QA xác nhận Đạt</b> mới cấp
    phát được, đúng mã xuất nhập đã hiệu chuẩn và cho đúng dòng đề nghị đó.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/hieu-chuan.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Xác nhận hiệu chuẩn'])

<h3>Quy trình 3 bước</h3>
<ol class="ug-steps">
    <li><b>Kho đề nghị hiệu chuẩn</b>: ở tab <a href="#cho-cap-phat">Chờ cấp phát</a>, chọn mã xuất nhập và số lượng ở cột
        Cấp Phát, bấm <b>Đề nghị HC</b>. Nhập <b>Seri No</b> cho từng đơn vị (nếu có) và ghi chú gửi QA. Phần này bị
        <b>giữ chỗ</b>, không ai cấp, chuyển hay cách ly được nữa.</li>
    <li><b>QA xác nhận</b> (phòng QA cùng công ty, có quyền <i>Xác Nhận Hiệu Chuẩn Vật Tư</i>): ở tab
        <b>Xác nhận hiệu chuẩn</b>, chọn <b>Đạt / Không đạt</b> cho <b>từng đơn vị</b>, ghi lý do với đơn vị không đạt,
        nhập lại mật khẩu. QA không sửa số lượng hay Seri No; được phép đạt một phần.</li>
    <li><b>Kho cấp phát</b> phần đạt ở tab Chờ cấp phát như bình thường.</li>
</ol>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>Phần <b>Không đạt</b> tự chuyển sang <a href="#cach-ly">Cách ly</a> của phòng đề nghị, chờ quyết định loại bỏ hoặc trả
        về kho. Phiếu còn chờ QA thì phòng đề nghị có thể huỷ để nhả phần giữ chỗ.</div>
</div>
