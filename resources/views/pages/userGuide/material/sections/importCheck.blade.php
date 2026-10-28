@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Danh sách các lô vừa nhập, đang nằm trong khu Biệt Trữ chờ kiểm tra. Kiểm tra xong, lô mới được vào tồn và được đề
    nghị, cấp phát.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/cho-kiem-tra.png', 'caption' => 'Màn hình Nhập › Vật Tư › tab Chờ kiểm tra'])

<h3>Xác nhận kiểm tra</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-clipboard-check mr-1"></i> Kiểm tra</span> trên dòng. Cửa sổ hiện mã xuất nhập, vật tư, số lượng, ngày nhập và vị trí biệt trữ.</li>
    <li>Chọn <b>Kết quả kiểm tra</b>: <b>Đạt</b> hoặc <b>Không đạt</b>.</li>
    <li><b>Đạt</b>: bắt buộc chọn <b>Định khu vị trí lưu trữ thật</b>. Có thể bổ sung số lô, số hoá đơn, ngày ký hoá đơn,
        hạn sử dụng, mục đích sử dụng.</li>
    <li><b>Không đạt</b>: bắt buộc ghi <b>Kết luận kiểm tra</b> (lý do).</li>
    <li>Bấm xác nhận. Dòng được cập nhật ngay trên sổ nhập mà không cần tải lại trang.</li>
</ol>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 160px">Kết quả</th>
                <th>Điều gì xảy ra</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-success">Kiểm tra Đạt</span></td>
                <td style="font-weight: 400">Lô được nhập kho, <b>cộng vào tồn</b>, chuyển về vị trí thật và sẵn sàng để đề nghị, cấp phát.</td>
            </tr>
            <tr>
                <td><span class="badge badge-danger">Không đạt</span></td>
                <td style="font-weight: 400"><b>Trả hàng</b>: lô không bao giờ vào tồn, mọi thông tin của lô giữ nguyên để tra cứu.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>Kết quả kiểm tra là <b>quyết định cuối</b>: lô đã Đạt hoặc Không đạt thì không kiểm tra lại được. Lô đang bị khoá
        phải mở khoá trước khi kiểm tra. Vật tư nhận từ phòng khác (liên phòng ban) không đi qua tab này mà kiểm tra ngay ở
        bước <a href="#lien-phong-ban">Nhận hàng</a>.</div>
</div>
