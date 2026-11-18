@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])
@include('pages.userGuide.shared.flowMapLink', ['lane' => 'cach-ly', 'no' => 4, 'label' => 'Cách ly - Loại bỏ - Huỷ'])

<p>Dành cho vật tư có phân loại <b>Cần Hiệu Chuẩn Trước Khi Dùng</b> khác <i>Không</i>. Có hai kiểu:</p>
<ul>
    <li><b>Cần HC trước khi cấp phát</b>: chỉ phần đã được <b>xác nhận Đạt</b> mới cấp phát được, đúng mã xuất nhập đã hiệu chuẩn
        và cho đúng dòng đề nghị đó.</li>
    <li><b>Cần HC sau khi cấp phát</b>: cấp phát bình thường, hệ thống tự sinh phiếu hiệu chuẩn cho phần vừa cấp để người
        xác nhận ký kết quả sau.</li>
</ul>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/hieu-chuan.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Xác nhận hiệu chuẩn'])

<h3>HC trước khi cấp phát: 3 bước</h3>
<ol class="ug-steps">
    <li><b>Kho đề nghị hiệu chuẩn</b>: ở tab <a href="#cho-cap-phat">Chờ cấp phát</a>, chọn mã xuất nhập và số lượng ở cột
        Cấp Phát, bấm <b>Đề nghị HC</b>. Hộp <b>Đề nghị hiệu chuẩn</b> mở ra:
        <ul class="mb-0 pl-3">
            <li>Nhập <b>Seri No</b> cho từng đơn vị (bắt buộc, không trùng trong phiếu).</li>
            <li><b>Người xác nhận hiệu chuẩn</b> (bắt buộc): chọn <b>Phòng</b> trước (số trong ngoặc là số người có quyền xác nhận
                ở phòng đó, phòng QA xếp đầu và chọn sẵn), rồi tick <b>một hay nhiều người</b> của phòng đó. Không chọn được chính mình.</li>
            <li><b>Ghi chú gửi người xác nhận</b> nếu cần, rồi bấm
                <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi hiệu chuẩn</span>.</li>
        </ul>
        Phần này bị <b>giữ chỗ</b>, không ai cấp, chuyển hay cách ly được nữa.</li>
    <li><b>Người được chọn xác nhận</b>: chuyển sang đúng phòng đã được chọn, mở tab <b>Xác nhận hiệu chuẩn</b> (hoặc từ Trang chủ),
        bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-ruler-combined"></i></span>, chọn <b>Đạt / Không đạt</b> cho
        <b>từng đơn vị</b>, ghi lý do với đơn vị không đạt, nhập lại mật khẩu. Người xác nhận không sửa số lượng hay Seri No;
        được phép đạt một phần.</li>
    <li><b>Kho cấp phát</b> phần đạt ở tab Chờ cấp phát như bình thường.</li>
</ol>

<h3>HC sau khi cấp phát</h3>
<p>Kho cấp phát như vật tư thường. Cấp xong, tab <b>Xác nhận hiệu chuẩn</b> có thêm phiếu cho phần vừa cấp, <b>chưa có người xác nhận</b>
    (cột Kết Quả ghi <i>Chưa chọn người xác nhận</i>): kho bấm nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-user-check"></i></span>
    để chọn người. Kết quả <b>Không đạt</b> không tự cách ly (hàng đã cấp đi), dòng ghi <i>"Đã cấp - cần thu hồi phần không đạt"</i>
    để phòng tự thu hồi. Phiếu HC sau cấp phát không huỷ được.</p>

<h3>Theo dõi và đổi người xác nhận</h3>
<ul>
    <li>Cột <b>Kết Quả</b> của phiếu đang chờ ghi <i>"Chờ xác nhận:"</i> kèm tên từng người được chọn và tên phòng.</li>
    <li>Phòng đề nghị (người có quyền cấp phát) bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-user-check"></i></span>
        ở cột Thao Tác để <b>chọn / đổi người xác nhận</b> của phiếu còn chờ, rồi bấm
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi xác nhận</span>. Người cũ không còn thấy phiếu.</li>
    <li>Chỉ người được chọn, đang làm việc ở đúng phòng đã chọn, mới thấy và ký được phiếu. Phiếu chưa chọn người (phiếu cũ) do
        người có quyền xác nhận ở phòng QA cùng công ty ký.</li>
</ul>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>HC trước khi cấp phát: phần <b>Không đạt</b> tự chuyển sang <a href="#cach-ly">Cách ly</a> của phòng đề nghị, chờ quyết định
        loại bỏ hoặc trả về kho. Phiếu còn chờ xác nhận thì phòng đề nghị có thể huỷ
        (<span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-times"></i></span>) để nhả phần giữ chỗ.</div>
</div>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Ô người xác nhận báo <i>"Chưa có người nào được gán quyền Xác Nhận Hiệu Chuẩn Vật Tư tại một phòng"</i> thì chưa gửi được:
        nhờ Admin gán quyền này cho người xác nhận tại phòng của họ (Phân Quyền › Người dùng).</div>
</div>
