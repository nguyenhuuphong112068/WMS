<p>Audit Trail là nhật ký do hệ thống tự ghi cho các sự kiện quan trọng: đăng nhập, đăng xuất, đăng nhập thất bại, khoá tài khoản,
    đổi mật khẩu, ký duyệt, ký sai mật khẩu, thêm - sửa - duyệt dữ liệu... Nhật ký <b>chỉ ghi thêm</b>: không có nút sửa hay xoá,
    kể cả với quản trị viên.</p>

<h3>Xem nhật ký</h3>
<ol class="ug-steps">
    <li>Mở <b>Lịch Sử Đăng Nhập › Audit Trail</b> (cần quyền xem).</li>
    <li>Chọn <b>Từ ngày</b> và <b>Đến ngày</b> (mặc định 3 ngày gần nhất), bảng tự tải lại ngay khi đổi ngày.</li>
    <li>Dùng ô tìm kiếm của bảng để lọc theo tên người dùng, hoạt động hoặc nội dung.</li>
</ol>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/audit-trail.png', 'caption' => 'Màn hình Audit Trail'])

<h3>Các cột</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 200px">Cột</th>
                <th>Ý nghĩa</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Thời Gian</td><td style="font-weight: 400">Giờ máy chủ lúc ghi, người dùng không chỉnh được.</td></tr>
            <tr><td>Tên Người Dùng</td><td style="font-weight: 400">Tài khoản thực hiện. Lần đăng nhập sai tên tài khoản vẫn được ghi
                với tên đã gõ. Tài khoản đã vô hiệu hoá vẫn hiện đủ dòng cũ.</td></tr>
            <tr><td>Hoạt Động</td><td style="font-weight: 400">Tên thao tác, ví dụ Login, Đăng nhập thất bại, Khoá tài khoản,
                Xác thực chữ ký thất bại.</td></tr>
            <tr><td>Giá Trị Cũ / Giá Trị Mới</td><td style="font-weight: 400">Dữ liệu trước và sau khi thay đổi, hoặc diễn giải của sự
                kiện.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>Cần biết <b>ai đăng nhập lúc nào, từ máy nào, phiên kéo dài bao lâu</b> thì xem <a href="#lich-su-dang-nhap">Lịch Sử Đăng Nhập</a>
        sẽ nhanh hơn. Audit Trail dùng để truy một thao tác cụ thể trên dữ liệu.</div>
</div>
