<p>Trang chỉ xem, mở được mà không cần đăng nhập, để ai cũng tra nhanh một hoá chất đang để ở đâu và còn bao nhiêu. Bấm liên kết
    <b><i class="fas fa-search"></i> Tra cứu tồn kho hoá chất (không cần đăng nhập)</b> dưới khung đăng nhập.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng ban', true, 'Chỉ tra trong một phòng ban mỗi lần.'],
    ['Tên hoặc mã hoá chất', false, 'Bỏ trống để xem toàn bộ hoá chất còn tồn của phòng.'],
]])

<ol class="ug-steps">
    <li>Bấm liên kết tra cứu dưới khung đăng nhập.</li>
    <li>Chọn <b>Phòng ban</b>, gõ tên hoặc mã hoá chất (hoặc bỏ trống), bấm <b>Tra cứu</b>.</li>
    <li>Xem số liệu tổng (kho, kệ/tủ, vị trí có hàng, mã lô, hoá chất) và các thẻ vị trí bên dưới; bấm một thẻ để xem chi tiết lô.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/public-lookup.png', 'caption' => 'Kết quả tra cứu toàn bộ hoá chất của một phòng'])

<p>Kết quả hiện thành từng thẻ theo <b>vị trí</b> (Kho / Phòng / Kệ-Tủ / Vị trí), mỗi thẻ liệt kê hoá chất và số lượng tồn tính
    đến hiện tại. Chỉ hiện lô còn tồn. Ô <b>Lọc nhanh trong kết quả</b> lọc tiếp theo vị trí, kệ/tủ hoặc tên hoá chất.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Trang này không cho sửa gì và không hiện thông tin người dùng. Muốn lấy hoặc dùng hoá chất vẫn phải đăng nhập và ghi
        phiếu sử dụng như bình thường.</div>
</div>
