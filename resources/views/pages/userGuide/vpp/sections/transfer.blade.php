@include('pages.userGuide.shared.flowMapLink', ['lane' => 'lien-phong-ban', 'no' => 2, 'label' => 'Đề nghị liên phòng ban'])

<p>Phòng <b>A</b> (đang thiếu) xin VPP từ kho của phòng <b>B</b> (đang giữ hàng). Hàng không mất đi mà chuyển từ tồn
    của B sang tồn của A.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/lien-phong-ban.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › VPP › tab Đề nghị liên phòng ban - Phòng ban khác'])

<h3>Các bước</h3>
<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: bấm tạo đề nghị, chọn <b>Gửi Đến Phòng Ban</b>, thêm VPP (chọn được mọi VPP đã duyệt của
        danh mục công ty), nhập số lượng, ngày mong muốn. Bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu tạm</span> hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</span>.
        Gửi là trình ký: phiếu chuyển <span class="ug-chip is-warn">Chờ ký</span> và phải qua đủ
        <a href="#ky-lien-phong-ban">2 bước ký</a> rồi mới tới B.</li>
    <li><b>B cấp phát</b>: phiếu hiện ở mục <b>Gửi đến</b> của B và tab Chờ cấp phát. B chọn lô (gợi ý FEFO/FIFO) rồi
        <b>Cấp phát</b>, hoặc <b>Từ chối cấp phát</b> từng mục. Cấp phát trừ tồn của B ngay, mục chuyển sang <i>chờ nhận</i>.</li>
    <li><b>A nhận hàng</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-inbox mr-1"></i> Nhận</span>. Bước nhận
        cũng là bước kiểm tra: ghi kết luận và chọn <b>định khu thật</b> của phòng A. Lô mới vào tồn của A ngay, mã lô giữ
        mã gốc kèm hậu tố <span class="ug-key">-CK1</span>, <span class="ug-key">-CK2</span>... để truy vết. Hàng không đạt thì bấm
        <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-undo mr-1"></i> Từ chối nhận</span>, tồn tự hoàn lại cho B.</li>
</ol>

<h3 id="ky-lien-phong-ban">Ký duyệt liên phòng ban (2 bước cố định)</h3>
<ol class="ug-steps">
    <li><b>Bước 1 - Quản Lý Bộ Phận phòng đề nghị</b> (phòng A).</li>
    <li><b>Bước 2 - Quản Lý Bộ Phận phòng cấp phát</b> (phòng B; đề nghị VPP đến HC là Hành Chánh).</li>
</ol>
<ul>
    <li>Người ký là mọi tài khoản có vai trò Quản Lý Bộ Phận của đúng phòng, ai ký trước cũng được; ký lần lượt bước 1 rồi bước 2.
        Trang chủ báo phiếu chờ ký; mở phiếu chi tiết rồi bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-signature mr-1"></i> Đồng ý</span> hoặc
        <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-times mr-1"></i> Từ chối</span>.</li>
    <li>Người ký được <b>duyệt một phần</b>: bỏ tick các mục muốn từ chối và ghi lý do cho từng mục; các mục còn lại đi tiếp.
        Từ chối cả phiếu thì phiếu quay về <b>Lưu tạm</b> kèm lý do cho A sửa rồi gửi lại.</li>
    <li>Ký và từ chối đều phải nhập lại mật khẩu. Không còn khai bước ký ở Dữ Liệu Gốc.</li>
    <li>Phòng nào chưa có tài khoản Quản Lý Bộ Phận thì không Gửi được, hệ thống báo
        <i>"Bước ký n (...) chưa có tài khoản ... nào của phòng ...".</i></li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Mọi phòng đều có sẵn dòng VPP Của Phòng và cả công ty dùng chung một đơn vị tính, nên nhận hàng không cần khai hay quy đổi
        đơn vị. Phiếu cấp phát liên phòng ban không sửa hay khoá được ở Sổ sử dụng VPP; muốn thu hồi thì A phải Từ chối nhận.</div>
</div>
