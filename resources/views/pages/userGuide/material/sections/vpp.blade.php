@include('pages.userGuide.shared.flowMapLink', ['lane' => 'vpp', 'no' => 2, 'label' => 'Đề nghị VPP đến HC'])

<p>Xin <b>văn phòng phẩm</b> từ kho VPP của phòng <b>Hành Chánh</b>. Thực chất đây là một
    <a href="#lien-phong-ban">đề nghị liên phòng ban</a> gửi tới Hành Chánh, nên các bước cấp phát và nhận hàng giống hệt.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/de-nghi-vpp.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › tab Đề nghị liên phòng ban - VPP đến HC'])

<h3>Hai tab con</h3>
<ul>
    <li><b>Đề nghị cấp phát VPP đến HC</b>: các đề nghị đã lập. Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát VPP đến HC</span>
        để lập tay. Phòng nhận do hệ thống tự xác định là Hành Chánh.</li>
    <li><b>Danh sách vật tư cần đề nghị Hành Chính</b>: vật tư Hành Chánh mua đang dưới ngưỡng tồn tối thiểu, hoặc đã được
        ghi nhớ. Tick chọn các dòng rồi gửi đề nghị thẳng từ đây. Số trên tab là số vật tư đang chờ.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Đề nghị gửi Hành Chánh <b>chỉ gồm vật tư có Bộ Phận Mua Hàng = Hành Chánh</b> (khai ở Danh mục vật tư công ty).
        Vật tư Cung Ứng mua thì lập <a href="#du-tru">phiếu dự trù</a>. Quy trình ký của đề nghị VPP khai ở
        <b>Dữ Liệu Gốc › Trình Ký Đề Nghị Liên Phòng Ban</b>, loại "Đề nghị cấp phát VPP đến HC".</div>
</div>
