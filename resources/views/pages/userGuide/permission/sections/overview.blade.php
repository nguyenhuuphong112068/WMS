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

<h3>Phạm vi làm việc và phòng ban chỉ xem</h3>
<p><b>Phạm vi làm việc</b> của bạn gồm phòng ban chính và các phòng ban được gán cho vai trò của bạn; có vai trò không giới hạn
    phòng ban (hoặc là Admin) thì phạm vi là mọi phòng. Bạn vẫn chuyển sang được phòng ngoài phạm vi để <b>xem</b> dữ liệu:</p>
<ul>
    <li>Quyền ở phòng đó chỉ gồm quyền <b>Xem màn hình</b> và <b>Xem tab</b> lấy từ mọi vai trò của bạn (cộng quyền cấp riêng).</li>
    <li>Không vai trò nào có hiệu lực ở phòng đó: không ký duyệt, không nằm trong danh sách người ký của phòng.</li>
    <li>Mọi thao tác ghi (thêm, sửa, khoá, duyệt, ký, nhập, cấp phát...) bị chặn với thông báo
        <i>"Phòng ban ... ngoài phạm vi làm việc của bạn - chỉ được xem, không thao tác được."</i></li>
</ul>

<h3>Quyền được sắp xếp thế nào</h3>
<p>Danh sách quyền chia theo <b>module trên menu trái</b>: Dữ Liệu Gốc, Danh Mục, Nhập, Đề Nghị - Sử Dụng, Tồn Kho, Dự Trù và
    Phân Quyền &amp; Lịch Sử. Trong mỗi module, quyền xếp tiếp theo <b>Vật Tư, Hoá Chất, Chất Chuẩn</b> và <b>Dùng Chung</b>.
    Mỗi nhóm quyền có <b>loại phụ trách</b> (Vật Tư / Hoá Chất / Chất Chuẩn, hoặc cả ba là Chung) để xếp cột trên bảng
    phân quyền: nhóm phụ trách một loại nằm ở loại đó, nhóm phụ trách 2-3 loại nằm ở <b>Chung</b>; Admin và Ban Giám Đốc luôn ở
    Chung. Loại phụ trách chỉ dùng để sắp xếp và lọc bảng, không cấp hay chặn quyền nào. Xem các bảng ở phần
    <a href="#bang-materData">Bảng Phân Quyền Chung</a>. Các bảng này đọc thẳng cấu hình Nhóm Quyền: đổi quyền trên màn hình
    Nhóm Quyền thì trang hướng dẫn đang mở ở tab khác tự cập nhật bảng, không cần tải lại trang.</p>

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
        Muốn một người <b>thao tác</b> được ở phòng khác thì gán vai trò cho phòng đó; chỉ cần xem thì không phải gán gì.</div>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Một số bước ký không chỉ dựa vào quyền mà còn dựa vào <b>tên vai trò</b>: người ký đề nghị cấp phát vật tư theo
        <b>Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư</b>, bước ký dự trù theo vai trò <b>Quản Lý Bộ Phận</b> và
        <b>Ban Giám Đốc</b>, người kiểm tra hoá chất cấm theo vai trò <b>Quản Lý Kho Hoá Chất</b>. Vai trò
        <b>Admin</b> luôn có toàn quyền ở mọi phòng ban.</div>
</div>
