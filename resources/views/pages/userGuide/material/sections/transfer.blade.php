@include('pages.userGuide.shared.flowMapLink', ['lane' => 'lien-phong-ban', 'no' => 2, 'label' => 'Đề nghị liên phòng ban'])

<p>Phòng <b>A</b> (đang thiếu) xin vật tư từ kho của phòng <b>B</b> (đang giữ hàng). Hàng không mất đi mà chuyển từ tồn
    của B sang tồn của A.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/lien-phong-ban.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Đề nghị liên phòng ban - Phòng ban khác'])

<h3>Ba bước</h3>
<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: bấm tạo đề nghị, chọn <b>Gửi Đến Phòng Ban</b>, thêm vật tư (chọn được mọi vật tư đã duyệt của
        danh mục công ty, kể cả vật tư A chưa khai), nhập số lượng, ngày mong muốn. Bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu tạm</span> hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</span>.
        Nếu công ty có khai bước ký liên phòng ban, phiếu đi qua các bước ký trước rồi mới tới B.</li>
    <li><b>B cấp phát</b>: phiếu hiện ở mục <b>Gửi đến</b> của B và tab Chờ cấp phát. B chọn lô (gợi ý FEFO/FIFO) rồi
        <b>Cấp phát</b>, hoặc <b>Từ chối cấp phát</b> từng mục. Cấp phát trừ tồn của B ngay, mục chuyển sang <i>chờ nhận</i>.</li>
    <li><b>A nhận hàng</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-inbox mr-1"></i> Nhận</span>. Bước nhận
        cũng là bước kiểm tra: ghi kết luận và chọn <b>định khu thật</b> của phòng A. Lô mới vào tồn của A ngay, mã lô giữ
        mã gốc kèm hậu tố <span class="ug-key">-CK1</span>, <span class="ug-key">-CK2</span>... để truy vết. Hàng không đạt thì bấm
        <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-undo mr-1"></i> Từ chối nhận</span>, tồn tự hoàn lại cho B.</li>
</ol>

<h3>Ký duyệt liên phòng ban</h3>
<ul>
    <li>Các bước ký khai ở <b>Dữ Liệu Gốc › Trình Ký Đề Nghị Liên Phòng Ban</b>. Không khai bước nào thì phiếu gửi thẳng tới B.</li>
    <li>Người ký được <b>duyệt một phần</b>: bỏ tick các mục muốn từ chối và ghi lý do cho từng mục; các mục còn lại đi tiếp.
        Muốn từ chối cả phiếu thì dùng nút <b>Từ chối</b>, phiếu quay về Lưu tạm cho A sửa.</li>
    <li>Ký và từ chối đều phải nhập lại mật khẩu.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>A phải khai vật tư ở <a href="#vat-tu-cua-phong">Vật Tư Của Phòng</a> trước khi bấm Nhận, để có đơn vị tính quy đổi.
        Phiếu cấp phát liên phòng ban không sửa hay khoá được ở Sổ cấp phát; muốn thu hồi thì A phải Từ chối nhận.</div>
</div>
