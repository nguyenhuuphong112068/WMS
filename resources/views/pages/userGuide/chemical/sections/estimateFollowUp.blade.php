<p>Sau khi phiếu dự trù được phê duyệt, phòng đề nghị và bộ phận mua hàng cùng theo dõi tới khi hàng về. Luồng giống hệt
    <a href="{{ route('pages.userGuide.show', 'material') }}#theo-doi-du-tru">dự trù vật tư</a>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/theo-doi-du-tru.png', 'caption' => 'Màn hình Dự Trù › Hoá Chất › tab Theo dõi dự trù / Đơn dự trù đã được phê duyệt'])

<ul>
    <li><b>Đơn dự trù đã được phê duyệt</b> (bộ phận mua hàng, quyền <i>Tiếp Nhận Xử Lý Dự Trù Hoá Chất</i>): bấm <b>Tiếp nhận</b>
        phiếu mới duyệt, rồi cập nhật <b>Ngày Hẹn Đáp Ứng</b> cho từng tháng của từng mặt hàng. Mỗi lần đổi hẹn đều lưu lịch sử.</li>
    <li><b>Theo dõi dự trù</b>: từng mặt hàng với ngày mong muốn và ngày hẹn; cột <b>Trao Đổi</b> để hai bên nhắn tin trên từng mặt
        hàng. Phòng đề nghị đánh dấu <b>Hoàn thành</b> khi đã nhận đủ hàng.</li>
    <li><b>Huỷ một mặt hàng</b>: phòng đề nghị bấm Huỷ, bộ phận mua hàng <b>Đồng ý huỷ</b> hoặc <b>Không đồng ý</b>. Đủ cả hai bên mới huỷ.</li>
</ul>
<p class="mb-0">Trạng thái tiếp nhận:
    @foreach (config('estimate.reception_statuses') as $label)
        <span class="ug-chip">{{ $label }}</span>
    @endforeach
    . Hoá chất luôn do <b>Cung Ứng</b> mua.
</p>
