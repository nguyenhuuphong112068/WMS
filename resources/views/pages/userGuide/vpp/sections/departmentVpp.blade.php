@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Mỗi phòng khai <b>cách dùng riêng</b> của từng VPP: phân loại của phòng, ngưỡng tồn, chỗ để. Phòng <b>không phải tự thêm</b>
    VPP: thêm một mã ở <a href="#danh-muc-cong-ty">Danh mục VPP công ty</a> là mọi phòng ban có ngay một dòng tương ứng, nên số
    mã của phòng luôn bằng số mã chung.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/vat-tu-cua-phong.png', 'caption' => 'Màn hình Danh Mục › VPP › tab VPP của phòng'])

<h3>Các cột trên bảng</h3>
<ul>
    <li><b>Nhóm Dự Trù</b> và <b>Đơn Vị</b> lấy từ danh mục công ty (chỉ xem).</li>
    <li><b>Phân Loại Của Phòng</b>: bộ nhóm riêng phòng tự khai.</li>
    <li><b>Ngưỡng Tồn Tối Thiểu / Tối Đa</b>, <b>Định Khu</b>, <b>Ghi Chú</b>: phần phòng tự sửa.</li>
    <li><b>Sử Dụng</b> theo trạng thái của mã ở danh mục công ty: <span class="ug-chip">Đang dùng</span>,
        <span class="ug-chip is-warn">Chờ duyệt</span> (mã chưa được duyệt ở danh mục công ty) hoặc
        <span class="ug-chip is-danger">Đã khoá</span> (mã đã khoá ở danh mục công ty). Bộ lọc <b>Sử dụng</b> lọc theo ba trạng thái này.</li>
</ul>

<h3>Sửa phần riêng của phòng</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> ở dòng cần sửa.</li>
    <li>Sửa các ô trong bảng dưới đây, ghi <b>Lý Do Điều Chỉnh</b> rồi bấm Lưu. Bảng cập nhật ngay dòng vừa sửa, không tải lại trang.</li>
</ol>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/vat-tu-cua-phong-form.png', 'caption' => 'Hộp thoại sửa - VPP Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phân Loại Của Phòng', false, 'Bộ nhóm riêng của phòng, khai ở <b>Dữ Liệu Gốc › Phân Loại</b>.'],
    ['Đơn Vị Tính', false, 'Chỉ hiện, không sửa: lấy theo <b>Danh mục VPP công ty</b>, cả công ty dùng chung một đơn vị nên không cần quy đổi đơn vị.'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn xuống dưới mức này thì VPP tự vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống nếu chưa đặt.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Tồn vượt mức này thì màn Nhập và Dự Trù cảnh báo trữ quá nhiều.'],
    ['Định Khu', false, 'Vị trí <b>dự kiến</b> để VPP; chỉ dùng để điền sẵn khi nhập. Vị trí thật của từng lô chọn lúc kiểm tra.'],
    ['Ghi Chú', false, 'Ví dụ: chỉ dùng cho tổ Hoá Lý.'],
    ['Lý Do Điều Chỉnh', true, 'Ghi vào lịch sử thay đổi.'],
]])

<h3>Đề nghị dự trù một VPP</h3>
<p>Nút <span class="btn btn-sm btn-outline-warning ug-ui"><i class="fas fa-bookmark"></i></span> ở dòng mở hộp <b>Đề nghị dự trù VPP này?</b>:
    nhập số lượng cần đề nghị + lý do dự trù. Ở phòng thường VPP được đưa vào danh sách cần đề nghị Hành Chánh của tab
    <a href="#de-nghi-vpp">Đề nghị VPP đến HC</a>; ở phòng Hành Chánh VPP vào <a href="#can-du-tru">Danh sách VPP cần dự trù</a>.
    Dòng mã đã khoá không có nút này.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Tab làm việc trên <b>phòng ban đang chọn</b>. Không có nút Thêm mới hay Khoá ở tab này: muốn thêm hoặc ngừng dùng một
        VPP thì thêm / khoá mã ở <a href="#danh-muc-cong-ty">Danh mục VPP công ty</a>, mọi phòng đổi theo.</div>
</div>
