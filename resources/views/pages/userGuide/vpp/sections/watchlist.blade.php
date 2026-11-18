@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Mua hàng'])

<p>Tập trung mọi VPP <b>đang cần mua</b> vào một chỗ, để lập phiếu mua hàng chỉ bằng vài cú tick. Tab chỉ có khi đang chọn
    <b>phòng Hành Chánh</b> (phòng duy nhất lập mua hàng VPP). VPP vào danh sách
    từ 2 nguồn (lọc được theo cột <b>Nguồn</b>):</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/can-du-tru.png', 'caption' => 'Màn hình Mua Hàng › VPP › tab Danh sách VPP cần mua hàng'])
<ol>
    <li><b>Dưới ngưỡng tối thiểu</b>: tồn của phòng đang dưới <b>Ngưỡng Tồn Tối Thiểu</b> khai ở VPP Của Phòng. Tự động.</li>
    <li><b>Người dùng đề nghị</b>: người dùng bấm <b>Đề nghị mua hàng VPP</b> (bắt buộc nhập lý do) trên dòng đề nghị cấp phát
        hoặc trên danh mục VPP Của Phòng.</li>
</ol>

<h3>Lập phiếu mua hàng từ danh sách</h3>
<ol class="ug-steps">
    <li>Tick chọn các VPP cần mua (một VPP thuộc nhiều nguồn chỉ hiện một dòng).</li>
    <li>Khai số lượng và tháng cần dùng cho từng dòng.</li>
    <li>Lưu. Hệ thống tạo phiếu mua hàng <b>Nháp</b> và mở thẳng trang chi tiết để bổ sung rồi <a href="#du-tru">trình ký</a>.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Phòng <b>Hành Chánh</b> (giữ kho VPP) thấy mọi VPP cần mua hàng để lập phiếu mua. Phòng khác chỉ thấy VPP
                ngoài danh mục; VPP trong danh mục xin từ kho ở <a href="#de-nghi-vpp">Danh sách VPP cần đề nghị Hành Chính</a> bên Sử Dụng.</li>
            <li>Bấm <b>Bỏ ghi nhớ</b> để gỡ một VPP. Nếu tồn vẫn dưới ngưỡng thì VPP sẽ tự hiện lại.</li>
        </ul>
    </div>
</div>
