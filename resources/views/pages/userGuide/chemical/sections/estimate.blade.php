<p>Phòng lập <b>phiếu dự trù</b> để đề nghị bộ phận <b>Cung Ứng</b> mua hoá chất cho một hoặc nhiều tháng. Cách làm giống
    <a href="{{ route('pages.userGuide.show', 'material') }}#du-tru">dự trù vật tư</a>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/du-tru.png', 'caption' => 'Màn hình Dự Trù › Hoá Chất › tab Danh sách phiếu'])

<h3>Lập phiếu</h3>
<ol class="ug-steps">
    <li>Ở tab <b>Danh sách phiếu</b>, bấm tạo phiếu, chọn tháng / năm dự trù và ghi chú.</li>
    <li>Thêm mặt hàng: chọn từ Danh Mục Hoá Chất, hoặc gõ tên hoá chất <b>chưa có trong danh mục</b>. Cột <b>Code Nhà Sản Xuất</b> của mỗi dòng là ô chọn:
        chọn một Code NSX đã khai báo trên Cổng TT quốc gia ở <a href="#danh-muc-cong-ty">Danh mục công ty</a> (có nhãn
        <i>✓ đã khai báo</i>), hoặc gõ code mới rồi <span class="ug-key">Enter</span>. Code chưa khai báo hiện cảnh báo vàng
        <i>"Code X chưa khai báo Cổng TTQG"</i>; hoá chất danh mục chưa khai báo lần nào hiện <i>"Hoá chất chưa khai báo Cổng TTQG"</i>.
        Cảnh báo <b>không chặn lưu</b>, lưu xong vẫn hiện thông báo vàng theo dòng để nhắc khai báo bổ sung. Khung chọn danh mục phòng cũng
        có cột Code NSX với badge <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Chưa khai báo Cổng TTQG</span> /
        <span class="badge badge-light border">Chưa có Code NSX</span>. Hộp <b>Sửa mặt hàng</b> cũng có ô Code Nhà Sản Xuất; trang chi
        tiết phiếu hiện <i>Code: X</i> dưới cột Hãng kèm badge cảnh báo, và bảng theo dõi dự trù hiện Code NSX.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/du-tru-them-mat-hang.png', 'caption' => 'Hộp thoại Thêm Mặt Hàng Dự Trù - ô chọn Code NSX và cảnh báo khai báo Cổng TTQG'])
    </li>
    <li>Nhập số lượng cho từng tháng cần dùng, ngày mong muốn giao, mục đích; đính kèm báo giá, catalogue nếu có.</li>
    <li>Bấm <b>Trình ký</b>, chọn đích danh người <b>Ban Giám Đốc</b> ký bước 2, nhập lại mật khẩu.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Với hoá chất thuộc diện <b>ngưỡng PL IV</b>, ngay khi chọn hoá chất và gõ số lượng, form báo trước tồn trữ toàn công ty
        sau khi mua so với ngưỡng. Hoá chất đang nằm trong một phiếu dự trù chưa xong thì không thêm lại được.</div>
</div>

<h3>Ký 2 bước</h3>
<ul>
    <li><b>Bước 1 - Quản Lý Bộ Phận</b> của phòng lập phiếu: ai có vai trò này trong phòng đều ký được.</li>
    <li><b>Bước 2 - Ban Giám Đốc</b>: người được chọn lúc trình ký.</li>
    <li>Ký và từ chối đều nhập lại mật khẩu. Bị từ chối ở bước nào cũng quay về <b>Bị từ chối</b>, sửa rồi trình ký lại từ đầu.
        Phiếu chỉ khoá, không xoá, để mã phiếu không bị cấp lại.</li>
</ul>
