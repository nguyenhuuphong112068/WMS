@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Mỗi phòng tự khai <b>những vật tư phòng mình dùng</b> và <b>cách dùng riêng</b>: đơn vị tính, ngưỡng tồn, chỗ để.
    Chỉ vật tư đã khai ở tab này mới <b>nhập kho</b> và <b>đề nghị nội bộ</b> được ở phòng.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/vat-tu-cua-phong.png', 'caption' => 'Màn hình Danh Mục › Vật Tư › tab Vật tư của phòng'])

<h3>Khai nhiều vật tư một lần</h3>
<p>Hộp thoại <b>Khai Vật Tư Cho Phòng</b> là một bảng, <b>mỗi dòng là một vật tư</b>; bấm <b>Lưu</b> một lần là khai được tất cả.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới vật tư phòng</span>.</li>
    <li>Đưa vật tư vào bảng bằng một trong hai cách:
        <ul>
            <li>Gõ mã / tên / nhà sản xuất vào ô tìm trên thanh công cụ: mỗi lần chọn thêm một dòng, ô vẫn mở để chọn tiếp
                (bấm ra ngoài để đóng).</li>
            <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-cubes"></i> Chọn nhiều từ danh mục công ty</span>,
                lọc theo nhà sản xuất / phân loại, tick nhiều vật tư (ô tick ở đầu cột chọn mọi dòng đang hiện) rồi bấm
                <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-arrow-down"></i> Thêm vào bảng</span>.</li>
        </ul>
        Vật tư đã có dòng trong bảng không thêm lần hai (ô tick bị khoá, nhãn <i>Đã có trong bảng</i>).</li>
    <li>Hàng <b>Điền nhanh cho mọi dòng</b> (dưới tiêu đề bảng): chọn Theo Giá Trị, Phân loại, Đơn vị hoặc Định khu ở đây là áp cho mọi dòng
        đang có và điền sẵn cho dòng thêm sau. Đơn vị tính của dòng mới vẫn ưu tiên đơn vị các phòng khác đã khai cho vật tư đó.</li>
    <li>Sửa từng dòng nếu cần, bấm <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> để bỏ dòng, rồi bấm <b>Lưu</b>.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Dòng chọn đơn vị khác với đơn vị phòng khác đang dùng sẽ hiện thêm khối <b>Quy Đổi Đơn Vị</b> ngay dưới dòng đó, phải khai
        hệ số. Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: lỗi ghi theo <b>"Dòng N: ..."</b> ở đầu hộp thoại.
        Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/vat-tu-cua-phong-form.png', 'caption' => 'Hộp thoại thêm mới - Vật Tư Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Vật Tư', true, 'Chỉ vật tư đã duyệt trong danh mục công ty mà phòng <b>chưa khai</b>. Mỗi phòng khai một vật tư <b>một lần</b>.'],
    ['Theo Giá Trị', true, '<b>Thấp</b> / <b>Cao</b>. Từ 13/11/2026 giá trị khai theo phòng (trước khai ở danh mục công ty), hiện thành chip ở cột Phân Loại; bộ lọc Phân loại có nhóm <b>Theo Giá Trị (phòng)</b>. Là tiêu chí điều kiện của Trình Ký Đề Nghị CP Vật Tư.'],
    ['Phân Loại Của Phòng', false, 'Bộ nhóm riêng của phòng, khai ở <b>Dữ Liệu Gốc › Phân Loại</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn cho vật tư này.'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn xuống dưới mức này thì vật tư tự vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống nếu chưa đặt.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Tồn vượt mức này thì màn Nhập và Dự Trù cảnh báo trữ quá nhiều.'],
    ['Định Khu', false, 'Vị trí <b>dự kiến</b> để vật tư; chỉ dùng để điền sẵn khi nhập. Vị trí thật của từng lô chọn lúc kiểm tra.'],
    ['Ghi Chú', false, 'Ví dụ: chỉ dùng cho tổ Hoá Lý.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Tab làm việc trên <b>phòng ban đang chọn</b>. Không có bước duyệt; muốn ngừng dùng thì Khoá (bắt buộc ghi lý do).
        Cột <b>Phòng Ban Đang Dùng</b> ở tab Danh mục công ty đọc từ chính các dòng khai ở đây.</div>
</div>
