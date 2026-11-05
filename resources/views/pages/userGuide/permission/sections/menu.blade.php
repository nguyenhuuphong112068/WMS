<p>Menu bên trái gồm <b>cột icon</b> luôn hiện và <b>bảng con</b> mở ngay cạnh cột khi bấm vào một icon. Cột icon chỉ hiện các
    mục bạn có quyền xem ở phòng ban đang chọn.</p>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/menu-trai.png', 'caption' => 'Cột icon và bảng con của nhóm Phân Quyền', 'width' => 260])

<h3>Mở một màn hình</h3>
<ol class="ug-steps">
    <li>Bấm icon của nhóm chức năng trên cột icon, ví dụ <b>Tồn kho</b>. Icon của nhóm chứa màn hình đang mở có vạch xanh bên trái.</li>
    <li>Bảng con hiện ra bên cạnh. Với Danh Mục, Nhập, Sử Dụng, Tồn Kho, Dự Trù, Thống Kê, bảng con là các thẻ
        <b>Vật Tư</b> / <b>Hoá Chất</b> / <b>Chất Chuẩn</b>; Dữ Liệu Gốc chia theo nhóm (Tổ chức, Danh mục dùng chung...).</li>
    <li>Bấm thẻ hoặc dòng cần mở. <b>Trang chủ</b> và <b>CSDL</b> không có bảng con, bấm là mở thẳng.</li>
</ol>

<h3>Ghim bảng con</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 200px">Trạng thái</th>
                <th>Cách hoạt động</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><i class="fas fa-thumbtack"></i> Đã ghim</td>
                <td style="font-weight: 400">Bảng con luôn mở cạnh cột icon, nội dung trang lùi sang phải. Khi mở trang mới, bảng con tự
                    hiện nhóm chứa màn hình đó.</td>
            </tr>
            <tr>
                <td>Bỏ ghim</td>
                <td style="font-weight: 400">Bảng con nổi đè lên trang, tự đóng khi bấm ra ngoài, bấm <kbd>Esc</kbd> hoặc nút
                    <i class="fas fa-times"></i>. Màn hình dữ liệu được rộng thêm.</td>
            </tr>
        </tbody>
    </table>
</div>
<p>Đổi trạng thái bằng nút <i class="fas fa-thumbtack"></i> trên đầu bảng con hoặc nút <i class="fas fa-bars"></i> ở góc trái
    thanh trên cùng. Lựa chọn được nhớ trên máy tính đang dùng. Trên màn hình nhỏ, menu ẩn đi; bấm
    <i class="fas fa-bars"></i> để mở.</p>

<h3>Tìm màn hình</h3>
<p>Nhấn <kbd>Ctrl</kbd> + <kbd>K</kbd> ở bất kỳ màn hình nào, gõ tên màn hình (gõ không dấu cũng được, ví dụ
    <i>ton hoa chat</i>), dùng <kbd>↑</kbd> <kbd>↓</kbd> để chọn rồi <kbd>Enter</kbd> để mở. Chỉ tìm được màn hình bạn có quyền xem.</p>

<h3>Đổi phòng ban</h3>
<p>Ô cuối cột icon là <b>phòng ban đang chọn</b>. Bấm vào ô này, chọn phòng ban trong bảng con (chia theo công ty nếu có nhiều
    công ty). Trang hiện tại tải lại theo phòng ban mới. Mọi người dùng đều chọn được mọi phòng ban:</p>
<ul>
    <li>Phòng trong <b>phạm vi làm việc</b> của bạn (icon <i class="fas fa-building"></i>): làm việc theo quyền của vai trò.</li>
    <li>Phòng ngoài phạm vi (icon con mắt <i class="fas fa-eye" style="color:#B45309"></i>): <b>chỉ xem</b>. Ô phòng ban chuyển
        màu cam và tiêu đề màn hình có nhãn <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #B45309; border: 1px solid #F59E0B;"><i class="fas fa-eye mr-1"></i>Chỉ xem</span>.
        Bạn thấy đúng các màn hình và tab được xem ở phòng mình, không có nút thao tác; mọi thao tác ghi đều bị chặn.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Đổi phòng ban thì quyền và các mục trên menu cũng đổi theo. Xem <a href="#tong-quan">Cách Hệ Thống Phân Quyền</a>.</div>
</div>
