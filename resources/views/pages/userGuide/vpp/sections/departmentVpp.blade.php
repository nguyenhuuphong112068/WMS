@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Mỗi phòng tự khai <b>những VPP phòng mình dùng</b> và <b>cách dùng riêng</b>: đơn vị tính, ngưỡng tồn, chỗ để.
    Chỉ VPP đã khai ở tab này mới <b>nhập kho</b> và <b>đề nghị nội bộ</b> được ở phòng.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/vat-tu-cua-phong.png', 'caption' => 'Màn hình Danh Mục › VPP › tab VPP của phòng'])

<h3>Khai nhiều VPP một lần</h3>
<p>Hộp thoại <b>Khai VPP Cho Phòng</b> là một bảng, <b>mỗi dòng là một VPP</b>; bấm <b>Lưu</b> một lần là khai được tất cả.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới VPP phòng</span>.</li>
    <li>Đưa VPP vào bảng bằng một trong hai cách:
        <ul>
            <li>Gõ mã / tên / nhà sản xuất vào ô tìm trên thanh công cụ: mỗi lần chọn thêm một dòng, ô vẫn mở để chọn tiếp
                (bấm ra ngoài để đóng).</li>
            <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-cubes"></i> Chọn nhiều từ danh mục công ty</span>,
                lọc theo nhà sản xuất / phân loại, tick nhiều VPP (ô tick ở đầu cột chọn mọi dòng đang hiện) rồi bấm
                <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-arrow-down"></i> Thêm vào bảng</span>.</li>
        </ul>
        VPP đã có dòng trong bảng không thêm lần hai (ô tick bị khoá, nhãn <i>Đã có trong bảng</i>).</li>
    <li>Hàng <b>Điền nhanh cho mọi dòng</b> (dưới tiêu đề bảng): chọn Phân loại, Đơn vị hoặc Định khu ở đây là áp cho mọi dòng
        đang có và điền sẵn cho dòng thêm sau. Đơn vị tính của dòng mới vẫn ưu tiên đơn vị các phòng khác đã khai cho VPP đó.</li>
    <li>Sửa từng dòng nếu cần, bấm <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> để bỏ dòng, rồi bấm <b>Lưu</b>.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Dòng chọn đơn vị khác với đơn vị phòng khác đang dùng sẽ hiện thêm khối <b>Quy Đổi Đơn Vị</b> ngay dưới dòng đó, phải khai
        hệ số. Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: lỗi ghi theo <b>"Dòng N: ..."</b> ở đầu hộp thoại.
        Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/vat-tu-cua-phong-form.png', 'caption' => 'Hộp thoại thêm mới - VPP Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['VPP', true, 'Chỉ VPP đã duyệt trong danh mục công ty mà phòng <b>chưa khai</b>. Mỗi phòng khai một VPP <b>một lần</b>.'],
    ['Phân Loại Của Phòng', false, 'Bộ nhóm riêng của phòng, khai ở <b>Dữ Liệu Gốc › Phân Loại</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn cho VPP này.'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn xuống dưới mức này thì VPP tự vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống nếu chưa đặt.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Tồn vượt mức này thì màn Nhập và Dự Trù cảnh báo trữ quá nhiều.'],
    ['Định Khu', false, 'Vị trí <b>dự kiến</b> để VPP; chỉ dùng để điền sẵn khi nhập. Vị trí thật của từng lô chọn lúc kiểm tra.'],
    ['Ghi Chú', false, 'Ví dụ: chỉ dùng cho tổ Hoá Lý.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Tab làm việc trên <b>phòng ban đang chọn</b>. Không có bước duyệt; muốn ngừng dùng thì Khoá (bắt buộc ghi lý do).
        Cột <b>Phòng Ban Đang Dùng</b> ở tab Danh mục công ty đọc từ chính các dòng khai ở đây.</div>
</div>
