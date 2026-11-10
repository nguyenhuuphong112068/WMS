<p>Mỗi phòng khai sẵn các <b>gói</b>: một <b>Tên sản phẩm</b> + một <b>Chỉ tiêu</b> kiểm và danh sách hoá chất cần dùng
    cho chỉ tiêu đó. Khi ghi <a href="#su-dung">phiếu Sử Dụng Hoá Chất</a>, chọn gói ở <b>Cách 3 · Theo sản phẩm - chỉ tiêu</b>
    là hệ thống gợi ý toàn bộ hoá chất của gói, không phải tìm từng lô.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/theo-san-pham.png', 'caption' => 'Màn hình Danh Mục › Hoá Chất › tab Danh sách hoá chất theo sản phẩm'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/theo-san-pham-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Sách Hoá Chất Theo Sản Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm', true, 'Gõ tìm trong Dữ Liệu Gốc › Tên Sản Phẩm (chỉ tên đang hoạt động).'],
    ['Chỉ Tiêu', true, 'Chọn trong Dữ Liệu Gốc › Chỉ Tiêu Kiểm. Trong một phòng, cặp <b>Sản phẩm + Chỉ tiêu</b> chỉ có một gói đang dùng.'],
    ['Thêm Hoá Chất', true, 'Gõ mã danh mục hoặc tên để thêm từng hoá chất vào bảng bên dưới. Chỉ gồm hoá chất phòng đang khai ở tab <a href="#hoa-chat-cua-phong">Hoá chất của phòng</a>; mỗi hoá chất chỉ thêm một lần. Gói phải có ít nhất 1, tối đa 100 hoá chất. Bảng có cột <b>Nhà Sản Xuất</b> (ô chọn tìm được cả theo NSX). Nút <b>Danh mục phòng</b> mở hộp chọn nhiều hoá chất cùng lúc: tìm, tick rồi bấm <b>Thêm đã chọn</b>.'],
    ['Lượng Dùng Gợi Ý', false, 'Lượng thường dùng cho một lần (theo đơn vị của phòng). Có nhập thì phiếu Sử Dụng điền sẵn vào Số Lượng Dùng; để trống thì người dùng tự nhập.'],
    ['Ghi Chú', false, 'Tối đa 500 ký tự.'],
    ['Lý Do Điều Chỉnh', true, 'Chỉ có khi sửa gói; được ghi vào Audit Trail cùng nội dung trước và sau khi sửa.'],
]])

<h3>Thao tác</h3>
<ul>
    <li><span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Thêm sản phẩm - chỉ tiêu</span>: tạo gói mới.</li>
    <li><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>: sửa tên sản phẩm, chỉ tiêu, danh sách hoá chất hoặc lượng gợi ý.</li>
    <li><span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-lock"></i></span> /
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-unlock"></i></span>: khoá gói không dùng nữa (không còn
        gợi ý ở phiếu Sử Dụng) hoặc mở khoá lại; cần nhập lý do.</li>
</ul>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Thêm / sửa / khoá gói cần quyền <b>Quản lý danh sách hoá chất theo sản phẩm</b>; xem tab cần quyền xem tab
        <b>Danh sách hoá chất theo sản phẩm</b>. Gói chỉ dùng trong phòng ban đang chọn.</div>
</div>
