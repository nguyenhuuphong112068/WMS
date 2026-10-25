<p>Hệ thống không cấp quyền cho từng người mà cấp theo <b>nhóm quyền</b> (vai trò). Mỗi người được gán một hoặc nhiều vai
    trò, mỗi vai trò có thể giới hạn trong một số phòng ban. Quyền thực tế của bạn tại một thời điểm là quyền của các vai trò
    áp dụng cho <b>phòng ban đang chọn</b>.</p>

<h3>Quyền của bạn được tính thế nào</h3>
<ol class="ug-steps">
    <li>Lấy các vai trò của bạn áp dụng cho phòng ban đang chọn: vai trò không giới hạn phòng ban, hoặc vai trò được gán đúng
        phòng ban đó.</li>
    <li>Gộp tất cả quyền đã tick cho các vai trò đó ở <b>Nhóm Quyền</b>.</li>
    <li>Áp quyền <b>cấp riêng</b> cho bạn (nếu có): <b>Cho phép</b> thì thêm, <b>Từ chối</b> thì bỏ, ở mọi phòng ban.</li>
    <li>Bỏ các <b>quyền con</b> thiếu quyền cha. Ví dụ quyền xem một tab chỉ có tác dụng khi có quyền xem màn hình chứa tab
        đó; quyền Trao Đổi Theo Dõi Dự Trù cần cả quyền xem tab Theo dõi dự trù.</li>
</ol>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 200px">Loại quyền</th>
                <th>Tác dụng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Xem màn hình</td>
                <td style="font-weight: 400">Hiện mục trên menu trái. Thiếu quyền này thì menu và màn hình bị ẩn.</td>
            </tr>
            <tr>
                <td>Xem tab</td>
                <td style="font-weight: 400">Hiện từng tab trong màn hình nhiều tab (Nhập, Sử Dụng, Tồn Kho, Dự Trù...).</td>
            </tr>
            <tr>
                <td>Thao tác</td>
                <td style="font-weight: 400">Hiện nút Thêm, Sửa, Duyệt, Huỷ, In nhãn, Ký... Thiếu quyền thì nút không hiện.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Đổi phòng ban đang làm việc thì quyền cũng đổi theo. Ở phòng này bạn có thể thấy nút Duyệt, sang phòng khác thì không.
        Danh sách phòng ban được chọn gồm phòng ban chính của bạn và các phòng ban gán cho vai trò của bạn; có vai trò không
        giới hạn phòng ban thì chọn được mọi phòng.</div>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Một số bước ký không chỉ dựa vào quyền mà còn dựa vào <b>tên vai trò</b>: người ký đề nghị cấp phát vật tư theo
        <b>Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư</b>, bước ký dự trù theo vai trò <b>Quản Lý Bộ Phận</b> và
        <b>Ban Giám Đốc</b>, người kiểm tra hoá chất cấm theo vai trò <b>Quản Lý Kho Hoá Chất</b>. Vai trò
        <b>Admin</b> luôn có toàn quyền ở mọi phòng ban.</div>
</div>
