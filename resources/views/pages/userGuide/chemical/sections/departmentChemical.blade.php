<p>Mỗi phòng khai <b>những hoá chất phòng mình dùng</b> và <b>cách dùng riêng</b>. Chỉ hoá chất đã khai ở đây mới
    <b>nhập kho</b> được ở phòng. Ô nào để trống thì lấy theo mặc định của danh mục.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/hoa-chat-cua-phong.png', 'caption' => 'Màn hình Danh Mục › Hoá Chất › tab Hoá chất của phòng'])

<h3>Khai nhiều hoá chất một lần</h3>
<p>Hộp thoại <b>Khai Hoá Chất Cho Phòng</b> là một bảng, <b>mỗi dòng là một hoá chất</b>; bấm <b>Lưu</b> một lần là khai được tất cả.</p>
<ol class="ug-steps">
    <li>Bấm nút <b>Thêm mới</b> trên tab.</li>
    <li>Gõ mã / tên / nhà sản xuất vào ô tìm trên thanh công cụ rồi chọn: mỗi lần chọn thêm một dòng, ô vẫn mở để chọn tiếp
        (bấm ra ngoài để đóng). Chỉ tìm thấy hoá chất đã duyệt mà phòng <b>chưa khai</b>; mã đã có dòng thì không thêm lần hai.</li>
    <li>Hàng <b>Điền nhanh cho mọi dòng</b> (dưới tiêu đề bảng): chọn Đơn vị hoặc Định khu ở đây là áp cho mọi dòng đang có và
        điền sẵn cho dòng thêm sau. Đơn vị tính của dòng mới vẫn ưu tiên đơn vị các phòng khác đang dùng cho mã đó.</li>
    <li>Sửa từng dòng nếu cần, bấm <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> để bỏ dòng, rồi bấm <b>Lưu</b>.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Dòng chọn đơn vị khác với đơn vị phòng khác đang dùng sẽ hiện thêm khối <b>Quy Đổi Đơn Vị</b> ngay dưới dòng đó, phải khai
        hệ số. Dòng đặt ở định khu cùng tầng với hoá chất tương kỵ hiện khung đỏ dưới dòng nhưng <b>vẫn lưu được</b> (xem lưu ý cuối mục). Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: lỗi ghi theo <b>"Dòng N: ..."</b> ở đầu hộp thoại.
        Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/hoa-chat-cua-phong-form.png', 'caption' => 'Hộp thoại thêm mới - Hoá Chất Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Hoá Chất', true, 'Chọn ở ô tìm trên thanh công cụ (chỉ hoá chất đã duyệt mà phòng chưa khai). Mỗi phòng khai một hoá chất <b>một lần</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn. Mọi màn Nhập / Sử dụng / Tồn của phòng đọc đơn vị từ đây.'],
    ['Khối Lượng Quy Đổi', false, 'Ô <b>1 đv = ... kg</b> hiện ngay dưới Đơn Vị Tính khi đơn vị thuộc nhóm đếm (chai, can, thùng...): khối lượng hoá chất (kg) trong 1 đơn vị. Để trống thì hệ thống không quy ra kg được để đối chiếu ngưỡng PL IV.'],
    ['Hạn Dùng Nội Bộ (tháng)', false, 'Để trống thì lấy hạn dùng mặc định của danh mục (gợi ý <i>DM: n</i> trong ô).'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn xuống dưới mức này thì màn Tồn Kho báo <b>Sắp hết</b> và hoá chất vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống thì cảnh báo khi tồn còn dưới 20% lượng nhập.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Tồn vượt mức này thì màn Nhập và Dự Trù cảnh báo trữ quá nhiều.'],
    ['Định Khu', false, 'Chỗ dự kiến để hoá chất. Ô chỉ hiện <b>mã vị trí</b> (vd. <i>QC2-INS-068/02/01/A1</i>), vẫn gõ được tên kho / kệ / cột / tầng để tìm. Chỗ nào cùng tầng với hoá chất tương kỵ có thêm dòng lý do màu đỏ (vẫn chọn được).'],
    ['Ghi Chú', false, 'Ví dụ: chỉ dùng cho kiểm nghiệm vi sinh.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Định khu nằm cùng tầng với hoá chất tương kỵ <b>không bị chặn</b>: hệ thống vẫn lưu, rồi hiện thông báo <b>Cảnh báo tương kỵ</b>
        nêu hoá chất nào, ở đâu, tương kỵ theo nhóm nào (tính cả các dòng vừa khai cùng lần) - nên chuyển sang tầng khác. Trên bảng, badge
        <b>Tương kỵ cùng tầng (n)</b> cảnh báo các hoá chất đang đặt gần nhau; bấm vào badge để chỉ hiện các mã tương kỵ với nhau.</div>
</div>
