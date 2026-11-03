<p>Mỗi màn hình Dữ Liệu Gốc thuộc một trong bốn phạm vi. Quyền được cấp ở màn hình <b>Phân Quyền › Nhóm Quyền</b>,
    theo phạm vi và chi tiết tới từng màn hình.</p>

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
                <td>Đơn Chất, Nhóm Nguy Hại Bảng B, Hoá Chất</td>
            </tr>
            <tr>
                <td>Chất chuẩn</td>
                <td>Tên Chuẩn, Chỉ Tiêu Kiểm</td>
            </tr>
            <tr>
                <td>Vật tư</td>
                <td>Tên Vật Tư, Phân Loại, Trình Ký Đề Nghị CP Vật Tư. Trình Ký Đề Nghị Liên Phòng Ban có bộ quyền riêng.</td>
            </tr>
        </tbody>
    </table>
</div>

<p>Mỗi phạm vi có ba quyền: <b>Xem</b>, <b>Thêm/Sửa/Khoá</b> (thêm, sửa, khoá / mở khoá) và <b>Duyệt/Từ Chối</b>. Dưới mỗi
    quyền là <b>quyền con theo từng màn hình</b> (ví dụ <i>Xem Dữ Liệu Gốc Vật Tư</i> › <i>Xem Tên Vật Tư</i>,
    <i>Xem Phân Loại</i>...). Một thao tác ở một màn hình chỉ được làm khi có cả quyền của phạm vi lẫn quyền con của màn
    hình đó; màn không có bước duyệt thì không có quyền Duyệt/Từ Chối. Nút nào bạn không có quyền sẽ không hiện trên màn
    hình, màn không có quyền Xem thì không hiện trên menu.</p>

<h3>Mỗi vai trò được làm gì</h3>
<p>Đọc trực tiếp từ <b>Phân Quyền › Nhóm Quyền</b> hiện tại, mỗi dòng là một màn hình (kèm phạm vi ở bảng trên). Cách thiết lập xem
    <a href="{{ route('pages.userGuide.show', 'permission') }}">Hướng dẫn Phân Quyền</a>.</p>

@include('pages.userGuide.shared.roleTable')

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Không thấy menu hoặc nút cần dùng? Liên hệ quản trị viên để được cấp quyền phù hợp.
        Ở màn hình <b>Khởi Tạo Dữ Liệu</b>, tải file mẫu chỉ dành cho Admin; phòng ban cần quyền <b>Đính Kèm File Khởi Tạo Dữ Liệu</b> để nộp file đã điền.</div>
</div>
