<p>Bảng dưới diễn giải mỗi vai trò được làm gì với hoá chất, đọc trực tiếp từ <b>Phân Quyền › Nhóm Quyền</b> hiện tại.
    Quyền tính theo phòng ban đang chọn; nút nào thiếu quyền sẽ không hiện. Cách thiết lập và bảng đầy đủ xem
    <a href="{{ route('pages.userGuide.show', 'permission') }}">Hướng dẫn Phân Quyền</a>.</p>

@include('pages.userGuide.shared.roleTable')

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Người kiểm tra hoá chất cấm phải có vai trò <b>Quản Lý Kho Hoá Chất</b> trong phòng. Màn hình
        <b>Ngưỡng Tồn Trữ PL IV</b> hiện với người có quyền Xem Tồn Kho Hoá Chất.</div>
</div>
