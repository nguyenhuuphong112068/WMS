@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])

<p>Vật tư <b>bắt buộc</b> phải có đề nghị được ký duyệt trước khi kho cấp phát. Đề nghị nội bộ chia 3 tab theo nguồn gốc:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/de-nghi-noi-bo.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Vật Tư › 3 tab Đề nghị nội bộ'])
<ul>
    <li><b>Đề Nghị Theo Định Kỳ</b>: hệ thống tự tạo từ <a href="#danh-sach-chu-ky">danh sách nội bộ theo chu kỳ</a>; không lập tay ở tab này.</li>
    <li><b>Đề Nghị Theo ĐG Rủi Ro</b>: đề nghị theo kết quả đánh giá rủi ro, lập tay.</li>
    <li><b>Đề Nghị Thường Quy</b>: đề nghị thường ngày, lập tay.</li>
</ul>

<h3>Lập đề nghị</h3>
<ol class="ug-steps">
    <li>Ở tab Thường Quy hoặc Theo ĐG Rủi Ro, bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát vật tư</span>.</li>
    <li>Nhập <b>Tiêu đề đề nghị</b>, <b>Ngày mong muốn</b> (ngày cần có vật tư) và <b>Ghi chú chung</b> nếu cần.</li>
    <li>Mỗi dòng: chọn <b>Vật tư (danh mục)</b>, hoặc gõ <b>tên tự nhập</b> cho vật tư ngoài danh mục. Nhập <b>SL đề nghị</b>,
        đơn vị, và <b>Đối tượng / Mục đích</b> (bấm <i>Gắn đối tượng</i> để chọn thiết bị tiêu thụ). Cột <b>Tồn</b> cho
        biết tồn khả dụng hiện tại.</li>
    <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu tạm</span> để lưu nháp, hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Trình ký</span> để gửi đi ký.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>Thấy vật tư sắp thiếu ngay lúc lập đề nghị? Bấm <b>Đề nghị dự trù vật tư</b> trên dòng, nhập số lượng và lý do.
        Vật tư sẽ hiện ở <a href="#can-du-tru">Danh sách vật tư cần dự trù</a>, dù đề nghị có lưu hay không.</div>
</div>

<h3>Quy trình ký</h3>
<ul>
    <li>Người ký <b>không do người lập chọn</b>. Hệ thống tự xếp các bước theo
        <b>Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư</b>, dựa vào phân loại của các vật tư trong phiếu. Không vai trò nào
        phải ký thì đề nghị được <b>duyệt thẳng</b> và chuyển sang kho.</li>
    <li>Ký lần lượt từng bước. Người đến lượt ký nhận thông báo, mở đề nghị và bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-signature mr-1"></i> Đồng ý</span> hoặc
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-times mr-1"></i> Từ chối</span>, rồi nhập lại mật khẩu.
        Từ chối bắt buộc ghi lý do.</li>
    <li>Ký xong bước cuối thì đề nghị <b>Đã duyệt</b>, chuyển sang <a href="#cho-cap-phat">Chờ cấp phát</a> và báo cho người cấp phát.</li>
</ul>

<h3>Trạng thái đề nghị</h3>
<p>
    @foreach (config('material.request_app_statuses') as $label)
        <span class="ug-chip">{{ $label }}</span>
    @endforeach
</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Chỉ <b>sửa</b>, <b>huỷ</b> hoặc <b>trình ký lại</b> được đề nghị đang <b>Nháp</b> hoặc <b>Bị từ chối</b>. Sửa dòng vật
        tư thì quy trình ký được tính lại từ đầu. Bị từ chối ở bất kỳ bước nào cũng phải sửa rồi trình ký lại từ bước 1.</div>
</div>
