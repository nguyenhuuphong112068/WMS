<p>Phòng <b>A</b> xin chất chuẩn từ kho của phòng <b>B</b>, theo đúng các bước như hoá chất, có <a href="#ky-lien-phong-ban-cc">2 bước ký</a>
    giữa bước gửi và bước cấp phát:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/lien-phong-ban.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Chất Chuẩn › tab Đề nghị cấp phát chuẩn liên phòng ban'])
<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: chọn phòng được đề nghị, thêm chuẩn (gõ mã, tên hoặc nhà sản xuất để tìm; đơn vị tự điền theo
        đơn vị phòng mình khai), số lượng, ngày mong muốn. Bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu</span> hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</span>.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/lien-phong-ban-tao.png', 'caption' => 'Hộp thoại Tạo Đề Nghị Cấp Phát Chuẩn Liên Phòng Ban'])
    </li>
    <li><b>Ký duyệt</b>: Quản Lý Bộ Phận phòng A rồi Quản Lý Bộ Phận phòng B ký.</li>
    <li><b>B cấp phát</b>: chọn ống trong kho của B và số lượng, hoặc từ chối từng mục. Tồn của B bị trừ ngay, mục chuyển sang chờ nhận.</li>
    <li><b>A nhận</b>: bước nhận cũng là bước kiểm tra; chọn định khu thật của A và có thể đánh dấu <b>chiết ống trước khi dùng</b> cho
        ống nhận về. Ống mới vào tồn của A ngay. Không đạt thì <b>Từ chối nhận</b>, tồn tự hoàn lại cho B.</li>
</ol>
<h3 id="ky-lien-phong-ban-cc">Ký duyệt 2 bước</h3>
<p>Bấm <b>Gửi</b> là trình ký: phiếu chuyển <span class="ug-chip is-warn">Chờ ký</span>, dưới trạng thái có chip từng bước ký,
    và phải qua đủ 2 bước cố định rồi mới tới phòng B:</p>
<ol class="ug-steps">
    <li><b>Bước 1 - Quản Lý Bộ Phận phòng đề nghị</b> (phòng A).</li>
    <li><b>Bước 2 - Quản Lý Bộ Phận phòng cấp phát</b> (phòng B; phòng B thấy phiếu ở <i>Chờ xử lý</i> khi tới bước của mình).</li>
</ol>
<ul>
    <li>Người ký là mọi tài khoản có vai trò Quản Lý Bộ Phận của đúng phòng. Trang chủ báo phiếu chờ ký; bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-file-signature mr-1"></i> Ký duyệt</span> trên dòng để mở phiếu
        chi tiết, rồi <b>Đồng ý</b> hoặc <b>Từ chối</b> và nhập <b>Mật khẩu</b> xác nhận.</li>
    <li>Bị từ chối thì phiếu quay về <b>Lưu tạm</b>, lý do từ chối hiện ngay trên phiếu để A sửa rồi gửi lại.</li>
    <li>Phòng chưa có tài khoản Quản Lý Bộ Phận thì không Gửi được, hệ thống báo
        <i>"Bước ký n (...) chưa có tài khoản ... nào của phòng ..."</i> và phiếu giữ Lưu tạm.</li>
</ul>

<p>Hai khung <b>Đề nghị phòng mình đã gửi đi</b> và <b>Đề nghị phòng ban khác gửi đến</b> đều có hai tab con:
    <b>Chờ xử lý</b> (luôn hiện đủ, không lọc ngày: phiếu chưa cấp xong hoặc còn mục chờ nhận) và
    <b>Đã hoàn thành</b> (đã cấp đủ, từ chối hoặc huỷ, lọc theo ngày lập).</p>
<p class="mb-0">A phải khai chuẩn ở <a href="#chuan-cua-phong">Chất Chuẩn Của Phòng</a> trước khi nhận.</p>
