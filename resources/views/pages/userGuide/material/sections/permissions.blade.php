<p>Bảng dưới diễn giải mỗi vai trò được làm gì với vật tư, đọc trực tiếp từ <b>Phân Quyền › Nhóm Quyền</b> hiện tại.
    Quyền tính theo phòng ban đang chọn; nút nào thiếu quyền sẽ không hiện. Cách thiết lập và bảng đầy đủ xem
    <a href="{{ route('pages.userGuide.show', 'permission') }}">Hướng dẫn Phân Quyền</a>.</p>

@include('pages.userGuide.shared.roleTable')

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Người ký đề nghị cấp phát do <b>Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư</b> quyết định, không chỉ dựa vào quyền.
        Thống Kê Vật Tư hiện với người có quyền <b>Xem Cấp Phát Vật Tư</b>.</div>
</div>
