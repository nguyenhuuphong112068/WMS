<p>Mỗi phòng khai <b>những chất chuẩn phòng mình dùng</b> và cách dùng riêng. Chỉ chuẩn đã khai ở đây mới <b>nhập kho</b> được ở
    phòng. Ô nào để trống thì lấy theo mặc định của danh mục.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Chất Chuẩn', true, 'Chọn từ danh mục công ty (đã duyệt). Mỗi phòng khai một chất chuẩn <b>một lần</b>.'],
    ['Đơn Vị Tính', true, 'Đơn vị phòng dùng để nhập, xuất và ghi tồn (mg, g, ml, ống...).'],
    ['Hạn dùng nội bộ (tháng)', false, 'Hạn sau khi mở ống của riêng phòng. Để trống thì lấy hạn mặc định của danh mục.'],
    ['Ngưỡng Tồn Tối Thiểu', false, 'Tồn dưới mức này thì chuẩn vào <a href="#can-du-tru">Danh sách cần dự trù</a>. Để trống thì cảnh báo khi còn dưới 20% lượng nhập.'],
    ['Ngưỡng Tồn Tối Đa', false, 'Vượt mức này thì màn Nhập và Dự Trù cảnh báo.'],
    ['Định Khu', false, 'Chỗ dự kiến để chuẩn, dùng để điền sẵn khi nhập.'],
    ['Ghi Chú', false, ''],
]])
