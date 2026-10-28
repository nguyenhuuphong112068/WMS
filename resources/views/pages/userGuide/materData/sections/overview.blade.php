<p>
    <b>Dữ Liệu Gốc</b> là nơi khai báo các thông tin nền mà mọi màn hình nghiệp vụ (Danh Mục, Nhập, Sử Dụng,
    Tồn, Dự Trù...) chọn lại khi thao tác. Dữ liệu gốc khai đúng và đủ thì các phiếu nghiệp vụ mới chọn được,
    báo cáo mới khớp.
</p>

<h3>Nên khai báo theo thứ tự nào?</h3>
<p>Màn hình sau chọn lại dữ liệu của màn hình trước, vì vậy hãy khai theo thứ tự dưới đây:</p>

<div class="ug-flow">
    <div class="ug-flow-step">
        <span class="ug-flow-no">Bước 1</span>
        <b>Tổ chức</b>
        <a href="#cong-ty">Công Ty</a> → <a href="#phong-ban">Phòng Ban</a> → <a href="#to">Tổ</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">Bước 2</span>
        <b>Danh mục dùng chung</b>
        <a href="#don-vi-tinh">Đơn Vị Tính</a>, <a href="#quy-cach-dong-goi">Quy Cách</a>,
        <a href="#dieu-kien-bao-quan">Điều Kiện Bảo Quản</a>, <a href="#nha-san-xuat">Nhà SX</a>,
        <a href="#nha-cung-cap">Nhà CC</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">Bước 3</span>
        <b>Tên hàng</b>
        <a href="#hoat-chat">Hoạt Chất</a> → <a href="#hoa-chat">Hoá Chất</a>;
        <a href="#ten-chuan">Tên Chuẩn</a>; <a href="#ten-vat-tu">Tên Vật Tư</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">Bước 4</span>
        <b>Kho của phòng</b>
        <a href="#dinh-khu">Định Khu</a> (kho → kệ → cột → tầng → vị trí)
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">Bước 5</span>
        <b>Quy trình ký</b>
        <a href="#phan-loai">Phân Loại</a> → <a href="#trinh-ky-cap-phat">Trình Ký CP Vật Tư</a>,
        <a href="#trinh-ky-lien-phong-ban">Liên Phòng Ban</a>
    </div>
</div>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>
        Hệ thống mới triển khai, cần nạp số lượng lớn? Quản trị viên có thể dùng
        <a href="#khoi-tao-du-lieu">Khởi Tạo Dữ Liệu</a> để điền toàn bộ dữ liệu gốc, danh mục, định khu và tồn đầu kỳ
        vào một file Excel mẫu rồi nạp một lần, thay vì nhập tay từng màn hình.
    </div>
</div>

<h3>Ba nhóm màn hình theo cách hoạt động</h3>
<ul>
    <li><span class="ug-chip is-warn">Có bước duyệt</span> - bản ghi mới thêm hoặc vừa sửa ở trạng thái
        <b>Chờ duyệt</b>; chỉ khi <b>Đã duyệt</b> mới được chọn ở các màn hình nghiệp vụ.</li>
    <li><span class="ug-chip">Theo phòng ban đang chọn</span> - màn hình chỉ làm việc trên phòng ban đang chọn ở
        ô cuối cột menu bên trái. Muốn khai cho phòng khác, đổi phòng ban trước rồi mới thao tác.</li>
    <li>Các màn hình còn lại: thêm là dùng được ngay, không cần duyệt.</li>
</ul>
