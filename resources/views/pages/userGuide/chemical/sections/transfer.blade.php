<p>Phòng <b>A</b> (đang thiếu) xin hoá chất từ kho của phòng <b>B</b>. Hàng chuyển từ tồn của B sang tồn của A.
    Đề nghị phải qua <a href="#ky-lien-phong-ban-hc">2 bước ký</a> trước khi tới phòng B.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/lien-phong-ban.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Hoá Chất › tab Đề nghị chuyển liên phòng ban'])

<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Tạo đề nghị chuyển liên phòng ban</span>,
        chọn phòng được đề nghị, thêm hoá chất, số lượng, ngày mong muốn rồi gửi (trình ký). Đề nghị còn ở trạng thái lưu thì vẫn sửa
        hoặc huỷ được.</li>
    <li><b>Ký duyệt</b>: Quản Lý Bộ Phận phòng A rồi Quản Lý Bộ Phận phòng B ký (xem bên dưới).</li>
    <li><b>B cấp phát</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-hand-holding-medical mr-1"></i> Cấp phát</span>,
        chọn lô trong kho của B (gợi ý FEFO/FIFO) và số lượng, hoặc <b>Từ chối cấp phát</b> từng mục. Cấp phát trừ tồn của B ngay,
        mục chuyển sang <i>chờ nhận</i>.</li>
    <li><b>A nhận hàng</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-inbox mr-1"></i> Nhận</span>. Bước nhận
        cũng là bước kiểm tra: ghi kết luận, chọn <b>định khu thật</b> của phòng A. Lô mới vào tồn của A ngay với mã gốc kèm hậu tố
        <span class="ug-key">-CK1</span>, <span class="ug-key">-CK2</span>... Hàng không đạt thì <b>Từ chối nhận</b>, tồn tự hoàn lại cho B.</li>
</ol>

<h3 id="ky-lien-phong-ban-hc">Ký duyệt 2 bước</h3>
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

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>A phải khai hoá chất ở <a href="#hoa-chat-cua-phong">Hoá Chất Của Phòng</a> trước khi bấm Nhận.</li>
            <li>Lô <b>chưa xác định hạn dùng nội bộ</b> chỉ được chuyển <b>nguyên cả lô</b> (lô chưa mở, phòng nhận tự xác định);
                muốn cấp lẻ thì B phải xác định hạn nội bộ trước.</li>
            <li>Lô nhận lẻ ở phòng A không được dùng vượt tồn, vì số lượng đã được phòng gửi cân chia sẵn.</li>
        </ul>
    </div>
</div>
