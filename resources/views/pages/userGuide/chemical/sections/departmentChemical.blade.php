<p>Mỗi phòng khai <b>những hoá chất phòng mình dùng</b> và <b>cách dùng riêng</b>. Chỉ hoá chất đã khai ở đây mới
    <b>nhập kho</b> được ở phòng. Ô nào để trống thì lấy theo mặc định của danh mục.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/hoa-chat-cua-phong.png', 'caption' => 'Màn hình Danh Mục › Hoá Chất › tab Hoá chất của phòng'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/hoa-chat-cua-phong-form.png', 'caption' => 'Hộp thoại thêm mới - Hoá Chất Của Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Hoá Chất', true, 'Chọn từ danh mục công ty (chỉ hoá chất đã duyệt). Mỗi phòng khai một hoá chất <b>một lần</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn. Mọi màn Nhập / Sử dụng / Tồn của phòng đọc đơn vị từ đây.'],
    ['Khối Lượng Quy Đổi', false, 'Chỉ dùng khi đơn vị thuộc nhóm đếm (chai, can, thùng...): khối lượng hoá chất (kg) trong 1 đơn vị. Để trống thì hệ thống không quy ra kg được để đối chiếu ngưỡng PL IV.'],
    ['Hạn Dùng Nội Bộ (tháng)', false, 'Để trống thì lấy hạn dùng mặc định của danh mục.'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn xuống dưới mức này thì màn Tồn Kho báo <b>Sắp hết</b> và hoá chất vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống thì cảnh báo khi tồn còn dưới 20% lượng nhập.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Tồn vượt mức này thì màn Nhập và Dự Trù cảnh báo trữ quá nhiều.'],
    ['Định Khu', false, 'Chỗ dự kiến để hoá chất. Chỗ nào cùng tầng với hoá chất tương kỵ sẽ bị khoá kèm dòng lý do màu đỏ.'],
    ['Ghi Chú', false, 'Ví dụ: chỉ dùng cho kiểm nghiệm vi sinh.'],
]])

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>Hệ thống <b>chặn lưu</b> nếu định khu đã chọn nằm cùng tầng với hoá chất tương kỵ. Trên bảng, badge
        <b>Tương kỵ cùng tầng (n)</b> cảnh báo các hoá chất đang đặt gần nhau; bấm vào badge để chỉ hiện các mã tương kỵ với nhau.</div>
</div>
