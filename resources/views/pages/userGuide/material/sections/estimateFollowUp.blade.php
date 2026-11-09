@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Sau khi phiếu dự trù được phê duyệt, phòng đề nghị và bộ phận mua hàng cùng theo dõi tới khi hàng về.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/theo-doi-du-tru.png', 'caption' => 'Màn hình Dự Trù › Vật Tư › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt'])

<h3>Tab "Đơn dự trù đã được phê duyệt": dành cho bộ phận mua hàng</h3>
<p>Chỉ hiện với người đang chọn đúng phòng mua hàng và có quyền <b>Tiếp Nhận Xử Lý Dự Trù Vật Tư</b>.</p>
<ol class="ug-steps">
    <li>Phiếu vừa duyệt ở trạng thái <b>Chờ tiếp nhận</b>. Bấm <b>Tiếp nhận</b> (cũng làm được ở Trang chủ); hệ thống ghi người và ngày tiếp nhận.</li>
    <li>Với từng vật tư, cập nhật <b>Ngày Hẹn Đáp Ứng</b> cho <b>từng tháng dự trù</b>, kèm lý do nếu đổi hẹn. Mỗi lần đổi đều
        lưu lịch sử để phòng đề nghị theo dõi. Chỉ hẹn được khi phiếu đã phê duyệt.</li>
    <li>Trạng thái tiếp nhận:
        @foreach (config('estimate.reception_statuses') as $label)
            <span class="ug-chip">{{ $label }}</span>
        @endforeach
    </li>
</ol>

<h4>Xuất Excel đơn dự trù đã duyệt</h4>
<ol class="ug-steps">
    <li>Xuất <b>một phiếu</b>: bấm nút <span class="ug-key"><i class="fas fa-file-excel"></i></span> ở cột Thao Tác của dòng phiếu.</li>
    <li>Xuất <b>nhiều phiếu vào cùng một file</b>: tick ô chọn đầu mỗi dòng (ô ở tiêu đề chọn tất cả đơn đang lọc theo Bộ phận / Tình trạng),
        rồi bấm <span class="ug-key"><i class="fas fa-file-excel"></i> Xuất Excel</span>; số trên nút là số đơn đã chọn.</li>
    <li>Sheet <b>Chi tiết</b>: mỗi dòng là một vật tư x một tháng cần (kèm Quy Cách, P/N), gồm bộ phận, mã phiếu, số lượng, ĐVT, ngày mong muốn,
        ngày hẹn đáp ứng và tình trạng giao. Mặt hàng đã huỷ không xuất.</li>
    <li>Khi xuất nhiều phiếu: các dòng xếp theo Mã Vật Tư, cột <b>Tổng SL Theo Mã</b> cộng số lượng các dòng cùng mã hàng và cùng ĐVT;
        thêm sheet <b>Tổng hợp theo mã</b> (tổng số lượng, số phiếu, bộ phận, mã phiếu). Mặt hàng ngoài danh mục gộp theo tên.</li>
</ol>

<h3>Tab "Theo dõi dự trù"</h3>
<p>Liệt kê từng mặt hàng: số lượng, ngày mong muốn, <b>ngày hẹn đáp ứng</b>, người tạo. Cột <b>Trao Đổi</b> để hai bên nhắn
    tin ngay trên từng mặt hàng (quyền <i>Trao Đổi Theo Dõi Dự Trù</i>). Phòng đề nghị đánh dấu <b>Hoàn thành</b> khi đã nhận đủ
    hàng (quyền <i>Theo Dõi Giao Hàng Dự Trù Vật Tư</i>).</p>

<h3>Huỷ một mặt hàng đã duyệt: cần cả hai bên đồng ý</h3>
<ol class="ug-steps">
    <li><b>Phòng đề nghị</b> bấm <b>Huỷ</b> trên mặt hàng ở trang chi tiết phiếu. Mặt hàng chuyển sang <b>Chờ xác nhận huỷ</b>.</li>
    <li><b>Bộ phận mua hàng</b> mở phiếu từ tab Đơn dự trù đã được phê duyệt, bấm <b>Đồng ý huỷ</b> hoặc <b>Không đồng ý</b>.</li>
    <li>Đủ cả hai bên đồng ý thì mặt hàng mới bị huỷ.</li>
</ol>
<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Bộ phận mua hàng không tự đề nghị huỷ được; chỉ xác nhận khi phòng đề nghị đã bấm huỷ. Vật tư trong danh mục theo
        <b>Bộ Phận Mua Hàng</b> đã khai; vật tư ngoài danh mục mặc định do Cung Ứng mua.</div>
</div>
