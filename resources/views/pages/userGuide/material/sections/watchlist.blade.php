@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Tập trung mọi vật tư <b>đang cần mua</b> vào một chỗ, để lập phiếu dự trù chỉ bằng vài cú tick. Vật tư vào danh sách
    từ 3 nguồn (lọc được theo cột <b>Nguồn</b>):</p>
<ol>
    <li><b>Dưới ngưỡng tối thiểu</b>: tồn của phòng đang dưới <b>Ngưỡng Tồn Tối Thiểu</b> khai ở Vật Tư Của Phòng. Tự động.</li>
    <li><b>Người dùng đề nghị</b>: người dùng bấm <b>Đề nghị dự trù vật tư</b> (bắt buộc nhập lý do) trên dòng đề nghị cấp phát
        hoặc trên danh mục Vật Tư Của Phòng.</li>
    <li><b>Thiếu tồn khả dụng cho tháng ...</b>: các dòng không đáp ứng ở <a href="#kha-dung-thang-toi">Khả dụng tháng tới</a>.
        Tự động, kèm số lượng gợi ý.</li>
</ol>

<h3>Lập phiếu dự trù từ danh sách</h3>
<ol class="ug-steps">
    <li>Tick chọn các vật tư cần mua (một vật tư thuộc nhiều nguồn chỉ hiện một dòng).</li>
    <li>Khai số lượng và tháng cần dùng cho từng dòng.</li>
    <li>Lưu. Hệ thống tạo phiếu dự trù <b>Nháp</b> và mở thẳng trang chi tiết để bổ sung rồi <a href="#du-tru">trình ký</a>.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Danh sách này chỉ gồm vật tư <b>Cung Ứng</b> mua (và vật tư ngoài danh mục). Vật tư Hành Chánh mua nằm ở
                <a href="#de-nghi-vpp">Danh sách vật tư cần đề nghị Hành Chính</a> bên Sử Dụng.</li>
            <li>Bấm <b>Bỏ ghi nhớ</b> để gỡ một vật tư. Nếu tồn vẫn dưới ngưỡng thì vật tư sẽ tự hiện lại.</li>
        </ul>
    </div>
</div>
