@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Tập trung mọi VPP <b>đang cần mua</b> vào một chỗ, để lập phiếu dự trù chỉ bằng vài cú tick. VPP vào danh sách
    từ 3 nguồn (lọc được theo cột <b>Nguồn</b>):</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/can-du-tru.png', 'caption' => 'Màn hình Dự Trù › VPP › tab Danh sách VPP cần dự trù'])
<ol>
    <li><b>Dưới ngưỡng tối thiểu</b>: tồn của phòng đang dưới <b>Ngưỡng Tồn Tối Thiểu</b> khai ở VPP Của Phòng. Tự động.</li>
    <li><b>Người dùng đề nghị</b>: người dùng bấm <b>Đề nghị dự trù VPP</b> (bắt buộc nhập lý do) trên dòng đề nghị cấp phát
        hoặc trên danh mục VPP Của Phòng.</li>
    <li><b>Thiếu tồn khả dụng cho tháng ...</b>: các dòng <b>Không đáp ứng</b> và dòng <b>Vừa đủ</b> có Cần Dự Trù &gt; 0 ở <a href="#kha-dung-thang-toi">Khả dụng tháng tới</a>.
        Tự động, số lượng gợi ý = Cần Dự Trù. Nhãn trên từng dòng ghi lý do cụ thể (<i>Thiếu X so với nhu cầu tháng MM/YYYY…</i> hoặc <i>Hết tháng MM/YYYY dư X &lt; ngưỡng tối thiểu Y</i>), lý do này điền sẵn vào <b>Lý do dự trù</b>.</li>
</ol>

<h3>Lập phiếu dự trù từ danh sách</h3>
<ol class="ug-steps">
    <li>Tick chọn các VPP cần mua (một VPP thuộc nhiều nguồn chỉ hiện một dòng).</li>
    <li>Khai số lượng và tháng cần dùng cho từng dòng.</li>
    <li>Lưu. Hệ thống tạo phiếu dự trù <b>Nháp</b> và mở thẳng trang chi tiết để bổ sung rồi <a href="#du-tru">trình ký</a>.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Phòng <b>Hành Chánh</b> (giữ kho VPP) thấy mọi VPP cần dự trù để lập phiếu mua. Phòng khác chỉ thấy VPP
                ngoài danh mục; VPP trong danh mục xin từ kho ở <a href="#de-nghi-vpp">Danh sách VPP cần đề nghị Hành Chính</a> bên Sử Dụng.</li>
            <li>Bấm <b>Bỏ ghi nhớ</b> để gỡ một VPP. Nếu tồn vẫn dưới ngưỡng thì VPP sẽ tự hiện lại.</li>
        </ul>
    </div>
</div>
