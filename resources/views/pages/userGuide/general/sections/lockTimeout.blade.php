<p>Hai cơ chế tự động chặn người lạ dùng tài khoản của bạn: khoá tạm khi đoán sai mật khẩu nhiều lần và tự đăng xuất khi bỏ
    máy không thao tác.</p>

<h3>Khoá tạm khi nhập sai mật khẩu</h3>
<ol class="ug-steps">
    <li>Mỗi lần nhập sai, hệ thống báo số lần sai, ví dụ <i>"PassWord Không Chính Xác... (sai 2/5 lần)"</i>.</li>
    <li>Sai liên tiếp <b>5 lần</b>: tài khoản bị khoá <b>15 phút</b>. Trong thời gian này nhập đúng mật khẩu cũng không vào được,
        hệ thống báo còn bao nhiêu phút.</li>
    <li>Hết 15 phút tài khoản tự mở. Cần vào gấp thì nhờ quản trị viên <a href="#quan-tri-tai-khoan">reset mật khẩu</a>, việc này
        mở khoá ngay.</li>
    <li>Đăng nhập đúng một lần là bộ đếm số lần sai về 0.</li>
</ol>

<h3>Tự đăng xuất khi không thao tác</h3>
<p>Không có thao tác nào (mở trang, bấm lọc, lưu...) trong <b>15 phút</b>, phiên làm việc tự kết thúc. Lần bấm tiếp theo sẽ đưa
    bạn về trang đăng nhập với thông báo <i>"Phiên làm việc đã hết hạn do không hoạt động quá 15 phút."</i></p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phần đang nhập dở trong hộp thoại (chưa bấm Lưu) sẽ mất khi hết phiên. Với phiếu dài, hãy lưu tạm thường xuyên.</div>
</div>

<h3>Tài khoản bị vô hiệu hoá</h3>
<p>Nhân viên nghỉ việc hoặc chuyển công tác sẽ bị quản trị viên vô hiệu hoá tài khoản. Đăng nhập khi đó nhận thông báo
    <i>"Tài khoản đã bị vô hiệu hoá..."</i>. Tài khoản không bị xoá, nên tên người thực hiện trên phiếu cũ và nhật ký vẫn giữ nguyên.</p>

<p>Mọi lần đăng nhập sai, bị khoá, hết phiên đều được ghi vào <a href="#lich-su-dang-nhap">Lịch Sử Đăng Nhập</a> và
    <a href="#audit-trail">Audit Trail</a>.</p>
