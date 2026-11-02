<p>Mỗi phòng khai sẵn các <b>gói</b>: một <b>Tên sản phẩm</b> + một <b>Chỉ tiêu</b> kiểm và danh sách chất chuẩn cần dùng
    cho chỉ tiêu đó. Khi lập <a href="#de-nghi-noi-bo">đề nghị cấp phát chuẩn nội bộ</a>, bấm <b>Theo sản phẩm - chỉ tiêu</b>
    và chọn gói là các dòng đề nghị được điền sẵn, không phải thêm từng chất chuẩn.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/theo-san-pham.png', 'caption' => 'Màn hình Danh Mục › Chất Chuẩn › tab Danh sách chất chuẩn theo sản phẩm'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/theo-san-pham-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Sách Chất Chuẩn Theo Sản Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm', true, 'Gõ tìm trong Dữ Liệu Gốc › Tên Sản Phẩm (chỉ tên đang hoạt động).'],
    ['Chỉ Tiêu', true, 'Chọn trong Dữ Liệu Gốc › Chỉ Tiêu Kiểm. Trong một phòng, cặp <b>Sản phẩm + Chỉ tiêu</b> chỉ có một gói đang dùng.'],
    ['Thêm Chất Chuẩn', true, 'Gõ mã danh mục, tên hoặc nhà sản xuất để thêm từng chất chuẩn vào bảng bên dưới. Chỉ gồm chất chuẩn phòng đang khai ở tab <a href="#chuan-cua-phong">Chất chuẩn của phòng</a>; mỗi chất chuẩn chỉ thêm một lần. Gói phải có ít nhất 1, tối đa 100 chất chuẩn.'],
    ['Số Lượng ĐN Gợi Ý', false, 'Số lượng thường đề nghị cho chỉ tiêu (theo đơn vị của phòng). Có nhập thì đề nghị điền sẵn vào Số Lượng ĐN; để trống thì người lập tự nhập.'],
    ['Ghi Chú', false, 'Tối đa 500 ký tự.'],
    ['Lý Do Điều Chỉnh', true, 'Chỉ có khi sửa gói; được ghi vào Audit Trail cùng nội dung trước và sau khi sửa.'],
]])

<h3>Thao tác</h3>
<ul>
    <li><span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Thêm sản phẩm - chỉ tiêu</span>: tạo gói mới.</li>
    <li><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>: sửa tên sản phẩm, chỉ tiêu, danh sách chất chuẩn hoặc số lượng gợi ý.</li>
    <li><span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-lock"></i></span> /
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-unlock"></i></span>: khoá gói không dùng nữa (không còn
        hiện khi lập đề nghị) hoặc mở khoá lại; cần nhập lý do.</li>
</ul>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Thêm / sửa / khoá gói cần quyền <b>Quản lý danh sách chất chuẩn theo sản phẩm</b>; xem tab cần quyền xem tab
        <b>Danh sách chất chuẩn theo sản phẩm</b>. Gói chỉ dùng trong phòng ban đang chọn.</div>
</div>
