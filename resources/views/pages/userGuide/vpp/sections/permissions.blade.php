<p>Bảng dưới diễn giải mỗi vai trò được làm gì với VPP, đọc trực tiếp từ <b>Phân Quyền › Nhóm Quyền</b> hiện tại.
    Quyền tính theo phòng ban đang chọn; nút nào thiếu quyền sẽ không hiện. Cách thiết lập và bảng đầy đủ xem
    <a href="{{ route('pages.userGuide.show', 'permission') }}">Hướng dẫn Phân Quyền</a>.</p>

@include('pages.userGuide.shared.roleTable')

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><ul class="mb-0 pl-3">
        <li><b>Sử Dụng VPP</b>: ghi phiếu ở tab <a href="#su-dung-vpp">Sử Dụng VPP</a> (trừ tồn ngay). Vai trò <b>Người Sử Dụng VPP</b> được cấp sẵn quyền này.</li>
        <li><b>Tạo Đề Nghị VPP Liên Phòng Ban / Đến HC</b>: lập và gửi đề nghị ở 2 tab liên phòng ban.</li>
        <li>Người <b>ký</b> đề nghị liên phòng ban / VPP đến HC xét theo <b>vai trò Quản Lý Bộ Phận</b> của phòng đề nghị và phòng cấp phát,
            không theo quyền. Người ký phiếu dự trù: Quản Lý Bộ Phận rồi Ban Giám Đốc.</li>
    </ul></div>
</div>
