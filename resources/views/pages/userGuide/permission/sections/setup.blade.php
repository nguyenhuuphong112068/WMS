<p>Thiết lập theo thứ tự: tạo nhóm quyền, tick quyền cho nhóm, gán nhóm quyền cho người dùng. Chỉ cấp quyền riêng cho
    một người khi thật sự cần ngoại lệ.</p>

<h3>1. Tạo nhóm quyền</h3>
<ol class="ug-steps">
    <li>Vào <b>Quản Trị › Phân Quyền › Nhóm Quyền</b>, bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-users-cog"></i> Quản Lý Nhóm Quyền</span>
        (quyền <i>Tạo / Sửa Nhóm Quyền</i>).</li>
    <li>Nhập <b>Tên Nhóm Quyền</b> và <b>Diễn Giải</b>, bấm <b>Lưu</b>. Sửa tên bằng nút
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-pen"></i></span>.</li>
    <li>Xoá bằng nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash"></i></span>. Không xoá được nhóm
        <b>Admin</b> và nhóm đang gán cho người dùng.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Không đổi tên các nhóm <b>Quản Lý Bộ Phân</b>, <b>Ban Giám Đốc</b>, <b>Quản Lý Kho Hóa Chất</b>, <b>Admin</b>. Hệ thống
        tìm người ký dự trù, người kiểm tra hoá chất cấm và tài khoản toàn quyền theo đúng các tên này.</div>
</div>

<h3>2. Tick quyền cho nhóm</h3>
<ol class="ug-steps">
    <li>Ở bảng Nhóm Quyền, mỗi cột là một nhóm quyền, mỗi dòng là một quyền, chia theo Quyền Chung, Vật Tư, Hoá Chất, Chất Chuẩn.</li>
    <li>Tick hoặc bỏ tick ô: hệ thống <b>lưu ngay</b>, không có nút Lưu (quyền <i>Phân Quyền Cho Nhóm</i>).</li>
    <li>Tick một quyền con thì quyền cha được tick theo; bỏ quyền cha thì các quyền con bị bỏ theo.</li>
    <li>Cột <b>Admin</b> luôn đủ quyền, không bỏ tick được.</li>
</ol>

<h3>3. Gán nhóm quyền cho người dùng</h3>
<ol class="ug-steps">
    <li>Vào <b>Quản Trị › Phân Quyền › User</b>, bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-plus"></i> Thêm</span>
        hoặc nút Sửa trên dòng người dùng.</li>
    <li>Chọn một hoặc nhiều nhóm ở ô <b>Phân Quyền (Chọn 1 hoặc nhiều)</b>.</li>
    <li>Ở <b>Phạm vi phòng ban của từng quyền</b>, chọn phòng ban cho từng nhóm. <b>Để trống</b> nghĩa là nhóm đó áp dụng cho
        mọi phòng ban. Ô Công ty chỉ để lọc nhanh danh sách phòng ban.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Nhóm chọn đầu tiên là <b>nhóm quyền chính</b> của người dùng. Khi hệ thống xét người dùng có thuộc một vai trò hay không
        (ví dụ tài khoản Admin), nhóm chính được tính ở mọi phòng ban. Ở bước ký dự trù, vai trò Quản Lý Bộ Phận và Ban Giám Đốc
        phải có phạm vi gồm đúng phòng lập phiếu.</div>
</div>

<h3>4. Cấp quyền riêng cho một người</h3>
<ol class="ug-steps">
    <li>Ở màn hình User, bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-user-shield"></i></span>
        <i>Cấp quyền riêng ngoài nhóm quyền</i> (quyền <i>Cấp Quyền Riêng Cho User</i>).</li>
    <li>Mỗi quyền chọn <b>Theo nhóm quyền</b> (giữ như nhóm), <b>Cho phép</b> (thêm quyền) hoặc <b>Từ chối</b> (chặn quyền dù
        nhóm có). Lưu ngay khi chọn; cột <b>Kết Quả</b> cho biết quyền cuối cùng.</li>
    <li>Quyền riêng áp dụng ở <b>mọi phòng ban</b>, không theo phạm vi của nhóm.</li>
</ol>
