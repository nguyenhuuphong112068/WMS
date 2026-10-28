<p>Đổi mật khẩu ngay trên trang đăng nhập, bấm liên kết <b>Đổi mật khẩu</b> dưới nút Đăng Nhập. Hệ thống tự mở khung này
    trong hai trường hợp bắt buộc bên dưới; đổi xong là vào thẳng hệ thống, không cần đăng nhập lại.</p>

<h3>Khi nào phải đổi</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 240px">Trường hợp</th>
                <th>Hệ thống xử lý</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Lần đăng nhập đầu tiên</td>
                <td style="font-weight: 400">Tài khoản mới tạo hoặc vừa được quản trị viên reset dùng <b>mật khẩu tạm</b>. Đăng nhập
                    đúng mật khẩu tạm thì hệ thống báo <i>"Đây là lần đăng nhập đầu tiên. Vui lòng đổi mật khẩu mới để tiếp tục."</i>
                    và chưa cho vào cho tới khi đổi.</td>
            </tr>
            <tr>
                <td>Mật khẩu hết hạn</td>
                <td style="font-weight: 400">Mỗi mật khẩu dùng được <b>90 ngày</b> kể từ lần đổi. Tới hạn, hệ thống báo
                    <i>"Mật khẩu đã hết hạn sử dụng..."</i> và buộc đổi.</td>
            </tr>
            <tr>
                <td>Tự đổi chủ động</td>
                <td style="font-weight: 400">Bất cứ lúc nào, ví dụ khi nghi ngờ người khác biết mật khẩu. Hạn 90 ngày tính lại từ
                    hôm đổi.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên tài khoản', true, 'Tự điền sẵn khi hệ thống buộc đổi.'],
    ['Mật khẩu hiện tại', true, 'Mật khẩu đang dùng, hoặc mật khẩu tạm quản trị viên vừa cấp.'],
    ['Mật khẩu mới', true, 'Tối thiểu <b>6 ký tự</b>, có đủ <b>chữ hoa, chữ thường, số và ký tự đặc biệt</b> (ví dụ <span class="ug-key">Kho@2026</span>). Không được trùng mật khẩu hiện tại và <b>5 mật khẩu gần nhất</b>.'],
    ['Xác nhận mật khẩu mới', true, 'Gõ lại đúng mật khẩu mới.'],
]])

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>Đổi mật khẩu thành công cũng xoá số lần nhập sai và mở khoá tài khoản. Mật khẩu chỉ được lưu dưới dạng mã hoá một chiều;
        không ai, kể cả quản trị viên, xem được mật khẩu của bạn. Quên mật khẩu thì nhờ quản trị viên
        <a href="#quan-tri-tai-khoan">reset mật khẩu tạm</a>.</div>
</div>
