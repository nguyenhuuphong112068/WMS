<p>Mỗi màn hình Dữ Liệu Gốc thuộc một trong bốn phạm vi. Quyền được cấp theo từng phạm vi, ở màn hình
    <b>Phân Quyền › Nhóm Quyền</b>.</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 160px">Phạm vi</th>
                <th>Các màn hình</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dùng chung</td>
                <td>Công Ty, Phòng Ban, Tổ, Đơn Vị Tính, Quy Cách Đóng Gói, Điều Kiện Bảo Quản, Nhà Sản Xuất, Nhà Cung
                    Cấp, Trạng Thái, Tên Sản Phẩm, Định Khu, Đối Tượng</td>
            </tr>
            <tr>
                <td>Hoá chất</td>
                <td>Hoạt Chất, Nhóm Nguy Hại Bảng B, Hoá Chất</td>
            </tr>
            <tr>
                <td>Chất chuẩn</td>
                <td>Tên Chuẩn, Chỉ Tiêu Kiểm</td>
            </tr>
            <tr>
                <td>Vật tư</td>
                <td>Tên Vật Tư, Phân Loại, Trình Ký Đề Nghị CP Vật Tư, Trình Ký Đề Nghị Liên Phòng Ban</td>
            </tr>
        </tbody>
    </table>
</div>

<p>Trong mỗi phạm vi, quyền chia nhỏ theo thao tác: <b>Xem</b>, <b>Thêm</b>, <b>Sửa</b>, <b>Duyệt</b>,
    <b>Từ chối</b>, <b>Khoá/Mở khoá</b>. Nút nào bạn không có quyền sẽ không hiện trên màn hình.</p>

<h3>Mỗi vai trò được làm gì</h3>
<p>Đọc trực tiếp từ <b>Phân Quyền › Nhóm Quyền</b> hiện tại, mỗi dòng là một phạm vi ở bảng trên. Cách thiết lập xem
    <a href="{{ route('pages.userGuide.show', 'permission') }}">Hướng dẫn Phân Quyền</a>.</p>

@include('pages.userGuide.shared.roleTable')

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Không thấy menu hoặc nút cần dùng? Liên hệ quản trị viên để được cấp quyền phù hợp.
        Màn hình <b>Khởi Tạo Dữ Liệu</b> chỉ dành cho tài khoản Admin.</div>
</div>
