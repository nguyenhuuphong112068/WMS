<p>Giống hệt <a href="{{ route('pages.userGuide.show', 'material') }}#theo-doi-du-tru">theo dõi dự trù vật tư</a>:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/theo-doi-du-tru.png', 'caption' => 'Màn hình Dự Trù › Chất Chuẩn › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt'])
<ul>
    <li><b>Đơn dự trù đã được phê duyệt</b> (quyền <i>Tiếp Nhận Xử Lý Dự Trù Chất Chuẩn</i>): Cung Ứng bấm <b>Tiếp nhận</b>, rồi cập nhật
        <b>Ngày Hẹn Đáp Ứng</b> cho từng tháng của từng mặt hàng.</li>
    <li><b>Theo dõi dự trù</b>: ngày mong muốn, ngày hẹn, cột <b>Trao Đổi</b> nhắn tin trên từng mặt hàng; phòng đề nghị đánh dấu
        <b>Hoàn thành</b> khi đã nhận đủ.</li>
    <li><b>Huỷ một mặt hàng</b> cần cả phòng đề nghị và bộ phận mua hàng đồng ý.</li>
</ul>
