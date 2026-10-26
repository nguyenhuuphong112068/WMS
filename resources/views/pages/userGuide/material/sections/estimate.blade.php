@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Phòng lập <b>phiếu dự trù</b> để đề nghị bộ phận <b>Cung Ứng</b> mua vật tư cho một hoặc nhiều tháng. Phiếu phải được
    ký đủ 2 bước mới chuyển tới Cung Ứng.</p>

<h3>Lập phiếu</h3>
<ol class="ug-steps">
    <li>Ở tab <b>Danh sách phiếu</b>, bấm tạo phiếu. Chọn <b>Tháng Dự Trù</b>, <b>Năm</b> và ghi chú. Mã phiếu tự sinh dạng
        <span class="ug-key">[Bộ phận]yymmdd.xx</span>.</li>
    <li>Mở chi tiết phiếu, thêm vật tư: chọn từ danh mục (hiện sẵn tồn hiện tại, ngưỡng, thời gian đặt hàng) hoặc gõ tên
        vật tư <b>chưa có trong danh mục</b> kèm thông tin kỹ thuật, Part Number.</li>
    <li>Nhập <b>Số Lượng Dự Trù</b> cho từng tháng cần dùng (thêm hoặc bỏ cột tháng được), <b>Ngày Mong Muốn Giao</b>, mục đích
        sử dụng. Đính kèm báo giá, ảnh, catalogue cho từng vật tư nếu có.</li>
    <li>Bấm <b>Trình ký</b>, chọn đích danh người <b>Ban Giám Đốc</b> ký bước 2, nhập lại mật khẩu.</li>
</ol>

<h3>Quy trình ký 2 bước</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 120px">Bước</th>
                <th>Ai ký</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Bước 1</td>
                <td style="font-weight: 400"><b>Quản Lý Bộ Phận</b> của phòng lập phiếu. Không chỉ định đích danh: bất kỳ ai có vai trò này trong phòng đều ký được.</td>
            </tr>
            <tr>
                <td>Bước 2</td>
                <td style="font-weight: 400"><b>Ban Giám Đốc</b>, người được chọn lúc trình ký.</td>
            </tr>
        </tbody>
    </table>
</div>
<p>Ký và từ chối đều nhập lại mật khẩu. Ký xong bước 2 thì phiếu <b>Đã phê duyệt</b> và chờ Cung Ứng
    <a href="#theo-doi-du-tru">tiếp nhận</a>. Trạng thái phiếu:
    @foreach (config('estimate.app_statuses') as $label)
        <span class="ug-chip">{{ $label }}</span>
    @endforeach
</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phiếu chưa có vật tư hoặc đang bị khoá thì không trình ký được. Bị từ chối ở bước nào cũng quay về <b>Bị từ chối</b>,
        sửa lại rồi trình ký lại từ đầu.</div>
</div>
