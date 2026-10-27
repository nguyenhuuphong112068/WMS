@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Ghi nhận từng lô vật tư nhập vào kho của phòng đang chọn. Mỗi lô có một <b>mã xuất nhập</b> riêng. Lô mới nhập
    luôn ở trạng thái <span class="badge badge-warning">Chờ kiểm tra</span> và <b>chưa cộng vào tồn</b> cho tới khi
    <a href="#cho-kiem-tra">kiểm tra Đạt</a>.</p>

<h3>Tạo phiếu nhập</h3>
<ol class="ug-steps">
    <li>Ở tab <b>Sổ nhập vật tư</b>, bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Nhập vật tư</span>.</li>
    <li>Bấm vào ô <b>Vật tư</b> để mở danh sách vật tư <b>phòng đang dùng</b>. Có thể lọc theo nhà sản xuất, phân loại,
        tình trạng tồn. Vật tư chưa có trong danh sách thì khai ở <a href="#vat-tu-cua-phong">Vật Tư Của Phòng</a> trước.</li>
    <li>Nhập <b>Số lượng / lô</b> theo đơn vị của phòng. Nhập nhiều lô cùng thông tin thì tăng <b>Số lần nhập</b> (tối đa 50),
        mỗi lô một mã.</li>
    <li>Điền thông tin chứng từ nếu có, chọn <b>Định Khu Tạm</b> trong khu Biệt Trữ, đính kèm hồ sơ rồi bấm <b>Lưu</b>.</li>
</ol>

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Vật tư', true, 'Chỉ vật tư đã khai ở Vật Tư Của Phòng và đang hoạt động.'],
    ['Số lượng / lô', true, 'Lớn hơn 0.'],
    ['Số lần nhập', false, '1 - 50. Tạo nhiều lô giống hệt nhau trong một lần lưu.'],
    ['Hạn sử dụng', false, 'Để trống nếu vật tư không có hạn. Lô hết hạn sẽ không cấp phát được.'],
    ['Số lô, Số hoá đơn, Ngày ký hoá đơn', false, 'Thông tin chứng từ; có thể bổ sung lúc kiểm tra.'],
    ['Định Khu Tạm (Biệt Trữ / Chờ Kiểm Tra)', false, 'Chỉ chọn được vị trí có phân loại <b>Biệt Trữ</b>. Vị trí thật chọn ở bước kiểm tra.'],
    ['Tài liệu đính kèm', false, 'Hoá đơn, phiếu giao hàng, CoA... tối đa 10MB mỗi file.'],
    ['Mục đích sử dụng, Ghi chú', false, 'Tối đa 500 ký tự.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><b>Ngày nhập</b> là ngày bấm Lưu, hệ thống tự ghi và không sửa được. Mã xuất nhập có dạng
        <span class="ug-key">M-07-7KPMR9J4WD</span> (M + mã phòng + chuỗi ngẫu nhiên).</div>
</div>

<h3>Các nút trên sổ nhập</h3>
<ul>
    <li><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> <b>Điều chỉnh</b>: sửa thông tin phiếu.
        Không đổi gì thì hệ thống không ghi nhận. Lô chưa kiểm tra vẫn chỉ được đặt trong khu Biệt Trữ. Ngày nhập không sửa
        được. Số nhỏ ở góc nút là số lần đã điều chỉnh, bấm vào để xem lịch sử.</li>
    <li><span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-qrcode"></i></span> <b>In nhãn</b>: in nhãn dán lô
        có mã QR, chọn số nhãn cần in (tối đa 100). Mỗi lần in được ghi vào Audit Trail.</li>
</ul>
<p class="mb-0">Cột <b>Tình Trạng</b>: <span class="badge badge-warning">Chờ kiểm tra</span>
    <span class="badge badge-success">Kiểm tra Đạt</span> <span class="badge badge-danger">Không đạt</span>.
    Biểu tượng cảnh báo bên cạnh vật tư nghĩa là tồn đang dưới ngưỡng tối thiểu.</p>
