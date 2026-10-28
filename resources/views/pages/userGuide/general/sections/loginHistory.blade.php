<p>Mỗi lần đăng nhập (thành công hay thất bại) là một dòng, ghi thời điểm vào, thao tác cuối, lúc kết thúc phiên, địa chỉ IP và
    trình duyệt. Menu <b>Lịch Sử Đăng Nhập</b> trên cột icon có hai màn hình.</p>

<h3>Của Tôi (mọi người dùng)</h3>
<p>Lịch sử đăng nhập của chính bạn, phiên đang dùng được đánh dấu. Nếu trong 30 ngày qua có lần nhập sai mật khẩu vào tài khoản
    của bạn, đầu trang hiện cảnh báo vàng kèm số lần. Bạn không nhớ đã nhập sai thì có thể người khác đang thử mật khẩu:
    <a href="#doi-mat-khau">đổi mật khẩu</a> ngay và báo quản trị viên.</p>

<h3>Toàn Hệ Thống (cần quyền)</h3>
<p>Dành cho quản trị viên, QA. Đầu trang là số lượt đăng nhập thành công hôm nay (kèm số người), số lượt không vào được hôm nay, số phiên đang mở và
    khối <b>Đang trực tuyến</b> liệt kê các phiên còn hoạt động.</p>

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Từ ngày / Đến ngày', false, 'Mặc định 7 ngày gần nhất.'],
    ['Tài khoản', false, 'Lọc theo một người.'],
    ['Kết quả', false, 'Thành công, Sai mật khẩu / tài khoản, Bị khoá, Tài khoản vô hiệu hoá, Phải đổi mật khẩu.'],
    ['Địa chỉ IP', false, 'Gõ phần đầu để lọc cả dải, ví dụ <span class="ug-key">192.168.1.</span>'],
    ['Phòng ban', false, 'Phòng ban chính của người dùng.'],
]])

<p>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-filter"></i> Lọc</span> để áp dụng,
    <span class="btn btn-sm btn-secondary ug-ui">Xoá lọc</span> để về mặc định, nút
    <span class="btn btn-sm btn-outline-success ug-ui"><i class="fas fa-file-excel"></i></span> để xuất Excel đúng bộ lọc đang chọn.</p>

<h3>Cột Kết thúc phiên</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 260px">Giá trị</th>
                <th>Ý nghĩa</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Tự đăng xuất</td><td style="font-weight: 400">Người dùng bấm nút đăng xuất.</td></tr>
            <tr><td>Hết phiên (không thao tác 15 phút)</td><td style="font-weight: 400">Hệ thống tự đăng xuất.</td></tr>
            <tr><td><span class="badge badge-success">Đang mở</span></td><td style="font-weight: 400">Phiên còn thao tác trong 15 phút
                gần đây, kèm giờ thao tác cuối.</td></tr>
            <tr><td>Hết phiên / đóng trình duyệt</td><td style="font-weight: 400">Đóng trình duyệt mà không quay lại; phiên coi như kết
                thúc ở lần thao tác cuối.</td></tr>
            <tr><td>—</td><td style="font-weight: 400">Lượt đăng nhập không thành công, không có phiên.</td></tr>
        </tbody>
    </table>
</div>
