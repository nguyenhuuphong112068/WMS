<p>Tổng lượng hoá chất đã sử dụng trong một khoảng thời gian, gom theo <b>mã danh mục</b>. Chọn từ ngày - đến ngày rồi bấm
    <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-filter mr-1"></i> Xem báo cáo</span>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/bao-cao-su-dung.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Hoá Chất › tab Báo cáo theo khoảng thời gian'])
<ul>
    <li>Mỗi dòng: số lượt xuất, tổng sử dụng theo đơn vị của phòng, và số lượng <b>quy đổi ra kg</b>.</li>
    <li>Đơn vị đếm (chai, thùng...) hoặc đổi thể tích sang khối lượng mà thiếu tỉ trọng thì cột kg để trống kèm lý do, thay vì hiện số sai.</li>
    <li>Chỉ tính phiếu còn hiệu lực.</li>
</ul>
