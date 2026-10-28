<p>Trang chủ là màn hình đầu tiên sau khi đăng nhập. Trang gom mọi đề nghị đang chờ bạn hoặc phòng ban của bạn xử lý ở tất cả
    các module, để bạn không phải mở lần lượt từng màn hình Dự Trù, Sử Dụng, Danh Mục... Số liệu tính theo
    <b>phòng ban đang chọn</b>; đổi phòng ban thì trang chủ đổi theo.</p>

<h3>Bố cục</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/home-hero.png', 'caption' => 'Lời chào và hàng số liệu tóm tắt đầu Trang chủ'])
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 220px">Khối</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Lời chào</td>
                <td style="font-weight: 400">Ngày hôm nay, họ tên, phòng ban đang chọn và công ty. Hàng số liệu tóm tắt:
                    <b>tổng đề nghị chờ</b>, <b>chờ bạn ký duyệt</b>, <b>chờ phòng ban xử lý</b>, <b>đã cấp cho bạn</b> (chỉ hiện khi
                    có) và <b><i class="fas fa-hourglass-half"></i> Chờ lâu nhất</b>. Bấm vào "Chờ lâu nhất" để mở ngay phiếu đã chờ
                    lâu nhất.</td>
            </tr>
            <tr>
                <td><a href="#ma-tran">Ma trận tổng quan</a></td>
                <td style="font-weight: 400">Bảng đếm số đề nghị theo nghiệp vụ × đối tượng. Chỉ hiện khi có ít nhất một đề nghị.</td>
            </tr>
            <tr>
                <td><a href="#the-de-nghi">Đề nghị chờ bạn xem xét</a></td>
                <td style="font-weight: 400">Các thẻ màu, mỗi thẻ là một loại việc của một đối tượng, kèm vài phiếu chờ lâu nhất.
                    Không có việc gì thì hiện <i>"Không có đề nghị nào đang chờ bạn xem xét"</i>.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Thanh trên cùng (mọi màn hình)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/home-topnav.png', 'caption' => 'Thanh trên cùng: nút hướng dẫn và chuông thông báo (khung đỏ)'])
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 220px">Nút</th>
                <th>Tác dụng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="ug-key"><i class="fas fa-bars"></i></span></td>
                <td style="font-weight: 400">Ghim / bỏ ghim menu bên trái.</td>
            </tr>
            <tr>
                <td><span class="ug-key"><i class="fas fa-question"></i></span></td>
                <td style="font-weight: 400">Mở đúng mục hướng dẫn của màn hình (và tab) đang xem, trong thẻ trình duyệt mới.</td>
            </tr>
            <tr>
                <td><span class="ug-key"><i class="far fa-bell"></i></span></td>
                <td style="font-weight: 400">Mở thanh <a href="#thong-bao">Thông báo</a> - lịch sử các đề nghị đã hiện ở Trang chủ
                    và thông báo kết quả. Số trên chuông là số dòng chưa đọc, tự cập nhật mỗi phút.</td>
            </tr>
            <tr>
                <td>Họ tên / vai trò</td>
                <td style="font-weight: 400">Người đang đăng nhập và vai trò chính.</td>
            </tr>
            <tr>
                <td><span class="ug-key" style="color:#dc3545"><i class="fas fa-sign-out-alt"></i></span></td>
                <td style="font-weight: 400"><a href="#dang-nhap">Đăng xuất</a>.</td>
            </tr>
        </tbody>
    </table>
</div>

<p>Bấm <b>Trang chủ</b> trên cột icon bên trái để quay lại trang này từ bất kỳ màn hình nào.</p>
