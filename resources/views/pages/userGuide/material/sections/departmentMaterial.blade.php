<p>Mỗi phòng tự khai <b>những vật tư phòng mình dùng</b> và <b>cách dùng riêng</b>: đơn vị tính, ngưỡng tồn, chỗ để.
    Chỉ vật tư đã khai ở tab này mới <b>nhập kho</b> và <b>đề nghị nội bộ</b> được ở phòng.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Vật Tư', true, 'Chọn từ danh mục công ty (chỉ vật tư đã duyệt). Mỗi phòng khai một vật tư <b>một lần</b>.'],
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
