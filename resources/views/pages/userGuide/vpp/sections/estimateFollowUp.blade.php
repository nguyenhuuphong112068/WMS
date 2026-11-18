@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Mua hàng'])

<p>Sau khi phiếu mua hàng được phê duyệt, chính <b>kho VPP Hành Chánh</b> (vai trò Quản lý / Tổ trưởng Kho VPP) tiếp nhận, hẹn ngày đáp ứng
    và theo dõi tới khi hàng về. Phòng lập và bộ phận mua hàng là một nên mọi việc làm ngay trên trang chi tiết phiếu.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/theo-doi-du-tru.png', 'caption' => 'Màn hình Mua Hàng › VPP › tab Theo dõi mua hàng / Đơn mua hàng đã được phê duyệt'])

<h3>Tab "Đơn mua hàng đã được phê duyệt": dành cho kho VPP Hành Chánh</h3>
<p>Chỉ hiện với người đang chọn phòng Hành Chánh và có quyền <b>Tiếp Nhận Xử Lý Mua Hàng VPP</b> (mặc định Quản lý Kho VPP, Tổ trưởng
    Kho VPP; Quản Lý Bộ Phận chỉ ký, không lập / sửa / huỷ / theo dõi giao hàng).</p>
<ol class="ug-steps">
    <li>Phiếu vừa duyệt ở trạng thái <b>Chờ tiếp nhận</b>. Bấm <b>Tiếp nhận</b> ngay trên trang chi tiết phiếu (hoặc ở Trang chủ); hệ thống ghi người và ngày tiếp nhận.</li>
    <li>Với từng VPP, cập nhật <b>Ngày Hẹn Đáp Ứng</b> cho <b>từng tháng mua hàng</b>, kèm lý do nếu đổi hẹn. Mỗi lần đổi đều
        lưu lịch sử để phòng đề nghị theo dõi. Chỉ hẹn được khi phiếu đã phê duyệt.</li>
    <li>Trạng thái tiếp nhận:
        @foreach (config('estimate.reception_statuses') as $label)
            <span class="ug-chip">{{ $label }}</span>
        @endforeach
    </li>
</ol>

<h4>Xuất Excel đơn mua hàng đã duyệt</h4>
<ol class="ug-steps">
    <li>Xuất <b>một phiếu</b>: bấm nút <span class="ug-key"><i class="fas fa-file-excel"></i></span> ở cột Thao Tác của dòng phiếu.</li>
    <li>Xuất <b>nhiều phiếu vào cùng một file</b>: tick ô chọn đầu mỗi dòng (ô ở tiêu đề chọn tất cả đơn đang lọc theo Bộ phận / Tình trạng),
        rồi bấm <span class="ug-key"><i class="fas fa-file-excel"></i> Xuất Excel</span>; số trên nút là số đơn đã chọn.</li>
    <li>Sheet <b>Chi tiết</b>: mỗi dòng là một VPP x một tháng cần, gồm bộ phận, mã phiếu, số lượng, ĐVT, ngày mong muốn,
        ngày hẹn đáp ứng và tình trạng giao. Mặt hàng đã huỷ không xuất.</li>
    <li>Khi xuất nhiều phiếu: các dòng xếp theo Mã VPP, cột <b>Tổng SL Theo Mã</b> cộng số lượng các dòng cùng mã hàng và cùng ĐVT;
        thêm sheet <b>Tổng hợp theo mã</b> (tổng số lượng, số phiếu, bộ phận, mã phiếu). Mặt hàng ngoài danh mục gộp theo tên.</li>
</ol>

<h3>Tab "Theo dõi mua hàng"</h3>
<p>Liệt kê từng mặt hàng: số lượng, ngày mong muốn, <b>ngày hẹn đáp ứng</b>, người tạo. Cột <b>Trao Đổi</b> để hai bên nhắn
    tin ngay trên từng mặt hàng (quyền <i>Trao Đổi Theo Dõi Mua Hàng</i>). Phòng đề nghị đánh dấu <b>Hoàn thành</b> khi đã nhận đủ
    hàng (quyền <i>Theo Dõi Giao Hàng Mua Hàng VPP</i>).</p>

<h3>Huỷ một mặt hàng đã duyệt</h3>
<p>Khác mua hàng Vật Tư / Hoá Chất / Chất Chuẩn (cần cả hai bên đồng ý), mua hàng VPP chỉ cần <b>một lần xác nhận</b>: ở trang chi tiết
    phiếu bấm <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-times"></i> Huỷ</span> trên mặt hàng, xác nhận
    <i>"Huỷ mặt hàng này?"</i> là mặt hàng bị huỷ ngay, <b>không hoàn tác được</b>.</p>
