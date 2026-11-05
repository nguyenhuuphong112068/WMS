<p>Mỗi phòng khai <b>những chất chuẩn phòng mình dùng</b> và cách dùng riêng. Chỉ chuẩn đã khai ở đây mới <b>nhập kho</b> được ở
    phòng. Ô nào để trống thì lấy theo mặc định của danh mục.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/chuan-cua-phong.png', 'caption' => 'Màn hình Danh Mục › Chất Chuẩn › tab Chất chuẩn của phòng'])

<h3>Khai nhiều chất chuẩn một lần</h3>
<p>Hộp thoại <b>Khai Chất Chuẩn Cho Phòng</b> là một bảng, <b>mỗi dòng là một chất chuẩn</b>; bấm <b>Lưu</b> một lần là khai được tất cả.</p>
<ol class="ug-steps">
    <li>Bấm nút <b>Thêm mới</b> trên tab.</li>
    <li>Gõ mã / tên / nhà sản xuất vào ô tìm trên thanh công cụ rồi chọn: mỗi lần chọn thêm một dòng, ô vẫn mở để chọn tiếp
        (bấm ra ngoài để đóng). Chỉ tìm thấy chất chuẩn đã duyệt mà phòng <b>chưa khai</b>; mã đã có dòng thì không thêm lần hai.</li>
    <li>Hàng <b>Điền nhanh cho mọi dòng</b> (dưới tiêu đề bảng): chọn Đơn vị hoặc Định khu ở đây là áp cho mọi dòng đang có và
        điền sẵn cho dòng thêm sau. Đơn vị tính của dòng mới vẫn ưu tiên đơn vị các phòng khác đang dùng cho mã đó.</li>
    <li>Sửa từng dòng nếu cần, bấm <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> để bỏ dòng, rồi bấm <b>Lưu</b>.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Dòng chọn đơn vị khác với đơn vị phòng khác đang dùng sẽ hiện thêm khối <b>Quy Đổi Đơn Vị</b> ngay dưới dòng đó, phải khai
        hệ số. Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: lỗi ghi theo <b>"Dòng N: ..."</b> ở đầu hộp thoại.
        Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/chuan-cua-phong-form.png', 'caption' => 'Hộp thoại thêm mới - Chất Chuẩn Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Chất Chuẩn', true, 'Chọn ở ô tìm trên thanh công cụ (chỉ chất chuẩn đã duyệt mà phòng chưa khai). Mỗi phòng khai một chất chuẩn <b>một lần</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn (mg, g, ml, ống...).'],
    ['Hạn Dùng Nội Bộ (tháng)', false, 'Hạn sau khi mở ống của riêng phòng. Để trống thì lấy hạn mặc định của danh mục (gợi ý <i>DM: n</i> trong ô).'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn dưới mức này thì chuẩn vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống thì cảnh báo khi còn dưới 20% lượng nhập.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Vượt mức này thì màn Nhập và Dự Trù cảnh báo.'],
    ['Định Khu', false, 'Chỗ dự kiến để chuẩn, dùng để điền sẵn khi nhập.'],
    ['Ghi Chú', false, ''],
]])
