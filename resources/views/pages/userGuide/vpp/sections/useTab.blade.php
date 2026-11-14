<p>VPP dùng trong phòng được <b>ghi sổ trực tiếp</b>: bấm Sử dụng VPP, chọn lô, nhập số lượng rồi Lưu là tồn bị trừ ngay và
    dòng sử dụng vào <a href="#so-cap-phat">Sổ sử dụng VPP</a>. Không có đề nghị nội bộ, trình ký, cấp phát hay Phiếu Tạm.
    Bảng dưới nút liệt kê các lần sử dụng <b>hôm nay</b> của phòng (giờ dùng, mục đích, người sử dụng).</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/su-dung-vpp.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › VPP › tab Sử Dụng VPP'])

<h3>Các bước</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Sử dụng VPP</span>, hộp thoại <b>Sử Dụng VPP</b> mở ra.</li>
    <li>Đưa lô vào bảng bằng một trong hai cách:
        <ul>
            <li><b>Cách 1 · Quét mã xuất nhập</b>: đưa máy quét vào mã trên nhãn lô, hoặc gõ mã rồi nhấn <span class="ug-key">Enter</span> / bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-search"></i> Tra mã</span>;
                nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> quét bằng camera của máy tính bảng.</li>
            <li><b>Cách 2 · Tồn kho của phòng</b>: bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-tasks"></i> Chọn Từ Tồn Kho</span> để mở bảng các lô còn tồn (mã xuất nhập, tên, mã VPP, vị trí, hạn dùng,
                tồn còn), tìm rồi bấm chọn lô. Lô đã hết hạn không chọn được.</li>
        </ul></li>
    <li>Ở mỗi dòng nhập <b>Số Lượng Dùng</b> và <b>Mục Đích</b>; nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-times"></i></span> bỏ dòng.</li>
    <li>Bấm <b>Lưu</b>. Hệ thống báo <i>Đã ghi nhận n dòng sử dụng VPP, tồn kho đã trừ!</i></li>
</ol>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/su-dung-vpp-form.png', 'caption' => 'Hộp thoại thêm mới - Sử Dụng VPP (Ghi Sổ)'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Số Lượng Dùng', true, 'Lớn hơn 0, tối đa phần tồn còn của lô cộng thêm <b>5%</b> (bù sai số đếm).'],
    ['Mục Đích', false, 'Dùng để làm gì. Tối đa 500 ký tự.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><b>Người sử dụng</b> luôn là tài khoản đang đăng nhập, thời điểm là lúc bấm Lưu - không chọn người khác được. Chỉ lô của
        phòng đang chọn, đã kiểm tra nhập Đạt và chưa hết hạn mới dùng được. Ghi sai thì điều chỉnh ở
        <a href="#so-cap-phat">Sổ sử dụng VPP</a>. Nút Sử dụng VPP cần quyền <b>Sử Dụng VPP</b>; vai trò <b>Người Sử Dụng VPP</b> được cấp sẵn quyền này.</div>
</div>
