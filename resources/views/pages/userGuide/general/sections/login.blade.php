<p>Mỗi người dùng có một tài khoản riêng do quản trị viên cấp. Không dùng chung tài khoản: mọi thao tác và chữ ký trên hệ thống
    được ghi theo tên người đăng nhập.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên tài khoản', true, 'Tên đăng nhập quản trị viên cấp cho bạn (username), không phải họ tên.'],
    ['Mật khẩu', true, 'Phân biệt chữ hoa, chữ thường. Bấm biểu tượng con mắt để xem lại mật khẩu vừa gõ.'],
]])

<h3>Các bước đăng nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/login.png', 'caption' => 'Trang đăng nhập', 'width' => 340])

<ol class="ug-steps">
    <li>Mở địa chỉ của hệ thống WMS trên trình duyệt.</li>
    <li>Nhập <b>Tên tài khoản</b> và <b>Mật khẩu</b>, bấm <b>Đăng nhập hệ thống</b>. Sai thì thông báo đỏ hiện trên đầu khung, kèm
        số lần đã sai.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/login-error.png', 'caption' => 'Báo sai mật khẩu, kèm số lần sai', 'width' => 340])
    </li>
    <li>Nếu là lần đăng nhập đầu tiên, hoặc mật khẩu đã hết hạn, hệ thống chuyển sang khung <a href="#doi-mat-khau">Đổi Mật Khẩu</a>.
        Đổi xong bạn được đưa thẳng vào <a href="#trang-chu">Trang chủ</a>.</li>
    <li>Sau khi vào, hệ thống chọn sẵn <b>phòng ban chính</b> của bạn. Được làm việc ở nhiều phòng thì đổi phòng ở ô cuối cột icon
        bên trái, xem <a href="{{ route('pages.userGuide.show', 'permission') }}#menu-trai">Thanh Menu Bên Trái</a>.</li>
</ol>

<h3>Đăng xuất</h3>
<p>Bấm nút <span class="ug-key" style="color:#dc3545"><i class="fas fa-sign-out-alt"></i></span> ở góc phải thanh trên cùng. Luôn
    đăng xuất khi rời máy, nhất là máy dùng chung trong phòng thí nghiệm hoặc kho. Nếu quên, hệ thống tự đăng xuất sau
    <b>15 phút</b> không thao tác (xem <a href="#khoa-het-phien">Khoá Tài Khoản &amp; Hết Phiên</a>).</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Không cho người khác mượn tài khoản, không lưu mật khẩu trên giấy dán cạnh máy. Theo 21 CFR Part 11, người đăng nhập chịu
        trách nhiệm cho mọi thao tác và chữ ký được ghi dưới tên mình.</div>
</div>
