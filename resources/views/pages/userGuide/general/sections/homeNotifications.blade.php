<p>Thanh <b>Thông báo</b> bên phải (bấm chuông <span class="ug-key"><i class="far fa-bell"></i></span> trên thanh trên cùng, hoặc nút
    <span class="ug-chip"><i class="fas fa-history"></i> Lịch sử thông báo</span> ở khối <a href="#the-de-nghi">Đề nghị chờ bạn xem xét</a>)
    là <b>lịch sử của chính các đề nghị đã hiện ở Trang chủ</b>. Mỗi phiếu xuất hiện trên thẻ Trang chủ được ghi thành một dòng ở đây;
    phiếu xử lý xong biến mất khỏi Trang chủ nhưng vẫn còn trong lịch sử với nhãn <b>Đã xử lý</b>.</p>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/home-notifications.png', 'caption' => 'Thanh Thông báo: cùng chip đối tượng, nhóm nghiệp vụ và nhãn ai phải làm như thẻ Trang chủ'])

<h3>Đọc một dòng</h3>
<ol class="ug-steps">
    <li>Viền trái và chip mang màu đối tượng giống thẻ Trang chủ: <b>Vật tư</b>, <b>Hoá chất</b>, <b>Chất chuẩn</b>, <b>Dùng chung</b>.</li>
    <li>Nhóm nghiệp vụ (Dự trù, Đề nghị nội bộ, Liên phòng ban, Văn phòng phẩm, KS chất lượng / hàng hỏng, Phê duyệt dữ liệu) và nhãn
        ai phải làm: <span class="ug-chip"><i class="fas fa-signature"></i> Bạn ký duyệt</span>,
        <span class="ug-chip is-warn"><i class="fas fa-people-carry"></i> Phòng xử lý</span>,
        <span class="ug-chip is-success"><i class="fas fa-user-check"></i> Cấp cho bạn</span>.</li>
    <li>Trạng thái: <span class="ug-chip is-warn"><i class="fas fa-hourglass-half"></i> Đang chờ</span> phiếu còn trên Trang chủ;
        <span class="ug-chip is-success"><i class="fas fa-check"></i> Đã xử lý</span> kèm giờ hệ thống phát hiện phiếu không còn chờ bạn.
        Phiếu đã xử lý mà lại tới lượt bạn (ví dụ bạn ký tiếp bước sau) thì quay về <b>Đang chờ</b> và lên đầu danh sách.</li>
    <li>Tên loại việc (giống tên thẻ), dòng mã phiếu · tiêu đề · thông tin thêm, và thời điểm phiếu bắt đầu chờ.</li>
    <li>Dòng có nhãn <span class="ug-chip"><i class="fas fa-bell"></i> Hệ thống</span> hoặc tên người gửi là thông báo <b>kết quả</b>
        (đề nghị đã duyệt / bị từ chối, kết quả hiệu chuẩn, hệ thống tự lập đề nghị theo chu kỳ...), không có trên Trang chủ vì không
        phải việc chờ làm.</li>
</ol>

<h3>Tab và nút</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 220px">Tab / nút</th>
                <th>Tác dụng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tất cả</td>
                <td style="font-weight: 400">Toàn bộ lịch sử, mới nhất lên đầu, nhóm theo ngày.</td>
            </tr>
            <tr>
                <td>Đang chờ</td>
                <td style="font-weight: 400">Chỉ các phiếu còn chờ - khớp với những gì đang hiện trên Trang chủ. Số cạnh tab là số phiếu đang chờ.</td>
            </tr>
            <tr>
                <td>Chưa đọc</td>
                <td style="font-weight: 400">Dòng chưa mở. Số cạnh tab cũng là số trên chuông.</td>
            </tr>
            <tr>
                <td><span class="ug-key"><i class="fas fa-check-double"></i></span> Đọc hết</td>
                <td style="font-weight: 400">Đánh dấu mọi dòng đã đọc.</td>
            </tr>
            <tr>
                <td>Xem thêm thông báo cũ hơn</td>
                <td style="font-weight: 400">Tải tiếp 30 dòng cũ hơn. Chỉ hiện khi còn.</td>
            </tr>
        </tbody>
    </table>
</div>

<p>Bấm một dòng để đánh dấu đã đọc và mở màn hình xử lý của phiếu. Bấm một phiếu trên thẻ Trang chủ cũng đánh dấu dòng tương ứng
    ở đây là đã đọc. Thanh Thông báo tự cập nhật mỗi phút ở mọi màn hình.</p>

<div class="ug-callout">
    <i class="fas fa-exchange-alt"></i>
    <div><b>Theo phòng ban đang chọn:</b> việc "Phòng xử lý" phụ thuộc phòng đang chọn. Đổi sang phòng khác thì phiếu của phòng cũ vẫn giữ
        <b>Đang chờ</b> trong lịch sử - chỉ chuyển sang <b>Đã xử lý</b> khi bạn đứng lại đúng phòng đó và phiếu không còn chờ nữa.</div>
</div>
