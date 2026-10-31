<p>Lập <b>phiếu dự trù</b> để Cung Ứng mua chất chuẩn. Cách làm giống hệt
    <a href="{{ route('pages.userGuide.show', 'chemical') }}#du-tru">dự trù hoá chất</a>:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/du-tru.png', 'caption' => 'Màn hình Dự Trù › Chất Chuẩn › tab Danh sách phiếu'])
<ol class="ug-steps">
    <li>Ở tab <b>Danh sách phiếu</b>, tạo phiếu, chọn tháng / năm dự trù.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/du-tru-lap-phieu.png', 'caption' => 'Hộp thoại Lập Phiếu Dự Trù Chất Chuẩn', 'width' => 420])
    </li>
    <li>Thêm mặt hàng từ danh mục hoặc gõ tên chuẩn chưa có trong danh mục, nhập số lượng từng tháng, ngày mong muốn giao, mục đích.</li>
    <li>Bấm <b>Trình ký</b>, chọn người <b>Ban Giám Đốc</b> ký bước 2, nhập lại mật khẩu.</li>
</ol>
<p class="mb-0">Ký 2 bước: <b>Quản Lý Bộ Phận</b> của phòng (ai có vai trò này đều ký được), rồi <b>Ban Giám Đốc</b> đã chọn. Từ chối ở bước nào
    cũng quay về Bị từ chối, sửa rồi trình ký lại từ đầu.</p>
