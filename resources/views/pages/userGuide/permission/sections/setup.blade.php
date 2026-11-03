<p>Thiết lập theo thứ tự: tạo nhóm quyền, tick quyền cho nhóm, gán nhóm quyền cho người dùng. Chỉ cấp quyền riêng cho
    một người khi thật sự cần ngoại lệ.</p>

<h3>1. Tạo nhóm quyền</h3>
<ol class="ug-steps">
    <li>Vào <b>Quản Trị › Phân Quyền › Nhóm Quyền</b>, bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-users-cog"></i> Quản Lý Nhóm Quyền</span>
        (quyền <i>Tạo / Sửa Nhóm Quyền</i>).</li>
    <li>Nhập <b>Tên Nhóm Quyền</b>, <b>Diễn Giải</b> và tick <b>Loại Phụ Trách</b> (Vật Tư / Hoá Chất / Chất Chuẩn, bắt buộc ít nhất
        một; nhóm dùng chung cho cả kho thì tick cả 3), bấm <b>Lưu</b>. Báo lỗi <i>Vui lòng chọn ít nhất một loại phụ trách</i>
        khi chưa tick loại nào. Sửa tên bằng nút
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-pen"></i></span>.</li>
    <li>Xoá bằng nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash"></i></span>. Không xoá được nhóm
        <b>Admin</b> và nhóm đang gán cho người dùng.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/thiet-lap-tao-nhom.png', 'caption' => 'Form nhóm quyền trong hộp thoại Quản Lý Nhóm Quyền: tick Loại Phụ Trách', 'width' => 560])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Không đổi tên các nhóm <b>Quản Lý Bộ Phân</b>, <b>Ban Giám Đốc</b>, <b>Quản Lý Kho Hóa Chất</b>, <b>Admin</b>. Hệ thống
        tìm người ký dự trù, người kiểm tra hoá chất cấm và tài khoản toàn quyền theo đúng các tên này.</div>
</div>

<h3>2. Tick quyền cho nhóm</h3>
<ol class="ug-steps">
    <li>Ở bảng Nhóm Quyền, mỗi cột là một nhóm quyền, mỗi dòng là một quyền, chia thành từng khối theo module trên menu trái (Dữ Liệu Gốc, Danh Mục, Nhập, Đề Nghị - Sử Dụng, Tồn Kho, Dự Trù, Phân Quyền &amp; Lịch Sử); trong mỗi khối quyền xếp tiếp theo Vật Tư, Hoá Chất, Chất Chuẩn, Dùng Chung.</li>
    <li>Các cột nhóm quyền được xếp tự động theo loại <b>Chung → Vật Tư → Hoá Chất → Chất Chuẩn</b> (hàng tiêu đề màu phía trên tên nhóm),
        trong cùng loại nhóm có nhiều quyền hơn đứng trước. Loại của nhóm lấy theo ô <b>Loại Phụ Trách</b> khi tạo / sửa nhóm quyền:
        tick 1 loại thì xếp vào cột loại đó, tick 2-3 loại thì xếp vào Chung (Admin và Ban Giám Đốc luôn ở Chung). Hàng <b>Số quyền đã cấp</b> ngay dưới tiêu đề đếm số ô đã tick của từng nhóm trong khối, tự cập nhật khi tick. Rê chuột lên tên nhóm để xem diễn giải, lên tên quyền để xem mã quyền và diễn giải quyền đó được làm gì
        (danh sách đầy đủ ở <b>Phân Quyền › Quyền</b>).</li>
    <li>Thu hẹp bảng bằng thanh lọc phía trên: ô <b>Tìm quyền theo tên</b>; nút <b>Tất cả / Vật Tư / Hoá Chất / Chất Chuẩn / Dùng Chung</b>
        để chỉ xem quyền của một loại (các cột nhóm quyền khác loại cũng được ẩn, cột nhóm Chung luôn giữ); nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-users"></i> Tất cả nhóm quyền</span>
        để chọn một hoặc nhiều nhóm quyền cần xem (các cột khác bị ẩn). Khi đã chọn nhóm quyền, tick <b>Chỉ quyền đã cấp</b> để chỉ
        hiện các quyền mà ít nhất một nhóm đang chọn đã có. Bộ lọc được trình duyệt nhớ cho lần mở sau.</li>
    <li>Tick hoặc bỏ tick ô: hệ thống <b>lưu ngay</b>, không có nút Lưu (quyền <i>Phân Quyền Cho Nhóm</i>). Tài khoản chỉ có quyền
        <i>Xem Nhóm Quyền</i> vẫn thấy các ô đã tick nhưng bấm không đổi được.</li>
    <li>Tick nhiều ô một lần: <b>bấm giữ chuột</b> vào một ô rồi <b>kéo</b> qua các ô khác (ngang, dọc đều được). Ô bắt đầu đổi
        trạng thái, mọi ô kéo qua nhận cùng trạng thái đó; mỗi ô vẫn lưu ngay. Cột Admin không bị bỏ tick khi kéo.</li>
    <li>Khi cuộn xuống, hàng tên nhóm quyền giữ ở ngay dưới thanh trên cùng để luôn biết ô đang xem thuộc nhóm nào.</li>
    <li>Tick một quyền con thì quyền cha được tick theo; bỏ quyền cha thì các quyền con bị bỏ theo.</li>
    <li>Khối <b>Dữ Liệu Gốc</b>: mỗi phạm vi (Vật Tư / Hoá Chất / Chất Chuẩn / Dùng Chung) có 3 quyền <b>Xem</b>,
        <b>Thêm/Sửa/Khoá</b>, <b>Duyệt/Từ Chối</b>; dưới mỗi quyền là quyền con của từng màn hình (Tên Vật Tư, Đơn Vị Tính...).
        Muốn cho một nhóm làm việc ở một màn hình, tick quyền con của màn hình đó (quyền phạm vi được tick theo).</li>
    <li>Cấp một quyền cho nhiều nhóm cùng lúc: rê chuột vào dòng quyền, bấm nút
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-check-double"></i></span> ở cuối ô tên quyền để tick
        quyền đó cho <b>mọi nhóm quyền đang hiện</b> (theo bộ lọc loại / nhóm quyền). Nếu các nhóm đang hiện đã có đủ thì bấm lại để
        bỏ tick hết, hệ thống hỏi xác nhận trước và giữ nguyên cột Admin.</li>
    <li>Cột <b>Admin</b> luôn đủ quyền, không bỏ tick được.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/thiet-lap-nhom-quyen.png', 'caption' => 'Bảng Nhóm Quyền: khối theo module, cột nhóm quyền gom theo loại, hàng Số quyền đã cấp dưới tiêu đề'])
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/thiet-lap-loc.png', 'caption' => 'Lọc theo loại Hoá Chất và mở danh sách nhóm quyền cần xem (khung đỏ)'])

<h3>3. Gán nhóm quyền cho người dùng</h3>
<ol class="ug-steps">
    <li>Vào <b>Quản Trị › Phân Quyền › User</b>, bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-plus"></i> Thêm</span>
        hoặc nút Sửa trên dòng người dùng.</li>
    <li>Chọn một hoặc nhiều nhóm ở ô <b>Phân Quyền (Chọn 1 hoặc nhiều)</b>.</li>
    <li>Ở <b>Phạm vi phòng ban của từng quyền</b>, chọn phòng ban cho từng nhóm. <b>Để trống</b> nghĩa là nhóm đó áp dụng cho
        mọi phòng ban. Ô Công ty chỉ để lọc nhanh danh sách phòng ban.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/thiet-lap-user.png', 'caption' => 'Hộp thoại Cập Nhật User: chọn nhóm quyền và phạm vi phòng ban của từng nhóm', 'width' => 600])

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
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/permission/thiet-lap-quyen-rieng.png', 'caption' => 'Hộp thoại Quyền Riêng: chọn Theo nhóm quyền / Cho phép / Từ chối cho từng quyền'])
