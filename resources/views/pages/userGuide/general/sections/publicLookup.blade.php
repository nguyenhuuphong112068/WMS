<p>Trang chỉ xem, mở được mà không cần đăng nhập, để ai cũng tra nhanh một <b>hoá chất, chất chuẩn hoặc vật tư</b> đang để ở đâu
    và còn bao nhiêu. Bấm nút <b><i class="fas fa-search"></i> Tra cứu tồn kho</b> ở góc trên bên phải trang đăng nhập, rồi chọn
    loại hàng ở 3 nút <b>Hoá chất | Chất chuẩn | Vật tư</b> đầu trang.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng ban', true, 'Chỉ tra trong một phòng ban mỗi lần.'],
    ['Tên, mã hoặc mã lô', false, 'Tên, mã danh mục hoặc mã lô trên nhãn (mã xuất nhập / mã ống chuẩn). Bỏ trống để xem toàn bộ hàng còn tồn của phòng. Nút <i class="fas fa-camera"></i> cạnh ô quét mã trên nhãn bằng camera, quét xong tự tra cứu.'],
]])

<ol class="ug-steps">
    <li>Bấm <b>Tra cứu tồn kho</b> ở trang đăng nhập, chọn loại hàng.</li>
    <li>Chọn <b>Phòng ban</b>, gõ (hoặc quét) tên / mã, bấm <b>Tra cứu</b>.</li>
    <li>Xem số liệu tổng (kho, kệ/tủ, vị trí có hàng, mã lô, số mặt hàng) và kết quả theo 3 kiểu ở nút <b>Thẻ | 3D | Mặt bằng</b>;
        bấm một thẻ hoặc thùng hàng để xem chi tiết lô.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/public-lookup.png', 'caption' => 'Tra một hoá chất ở chế độ Mặt bằng: kệ và thùng hàng khớp tô xanh ngọc, nhấp nháy'])

<p><b>Thẻ</b> liệt kê từng <b>vị trí</b> (Kho / Phòng / Kệ-Tủ / Vị trí) kèm mặt hàng và số lượng tồn tính đến hiện tại; viền đầu thẻ
    vàng = có lô còn dưới 30 ngày hạn dùng, đỏ = có lô đã hết hạn. <b>3D</b> dựng từng kệ/tủ, <b>Mặt bằng</b> xem cả kho/phòng theo
    mặt bằng đã khai ở Định Khu; vị trí khớp từ khoá sáng lên và nhấp nháy (ở 3D khung tự phóng tới). Phòng ban chưa khai định khu chỉ
    có kiểu Thẻ. Ô <b>Lọc nhanh</b> lọc tiếp trong kết quả theo vị trí, kệ/tủ, tên hoặc mã.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Trang này không cho sửa gì và không hiện thông tin người dùng. Muốn lấy hoặc dùng hàng vẫn phải đăng nhập và ghi phiếu sử
        dụng như bình thường. Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ.</div>
</div>
