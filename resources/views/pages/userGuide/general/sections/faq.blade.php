<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 38%">Hiện tượng</th>
                <th>Nguyên nhân và cách xử lý</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>"User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!"</td>
                <td style="font-weight: 400">Gõ sai tên tài khoản (không phải họ tên). Kiểm tra lại, hoặc hỏi quản trị viên tên tài khoản
                    của bạn.</td>
            </tr>
            <tr>
                <td>"PassWord Không Chính Xác... (sai n/5 lần)"</td>
                <td style="font-weight: 400">Sai mật khẩu. Kiểm tra phím <span class="ug-key">Caps Lock</span> và bộ gõ tiếng Việt
                    (Unikey có thể biến <i>aa</i> thành <i>â</i>). Bấm biểu tượng con mắt để xem lại chữ đã gõ.</td>
            </tr>
            <tr>
                <td>"Tài khoản đang bị khoá do nhập sai quá nhiều lần..."</td>
                <td style="font-weight: 400">Chờ hết số phút báo trên màn hình, hoặc nhờ quản trị viên reset mật khẩu. Xem
                    <a href="#khoa-het-phien">Khoá Tài Khoản &amp; Hết Phiên</a>.</td>
            </tr>
            <tr>
                <td>"Tài khoản đã bị vô hiệu hoá..."</td>
                <td style="font-weight: 400">Liên hệ quản trị viên.</td>
            </tr>
            <tr>
                <td>"Mật khẩu mới không đảm bảo độ phức tạp"</td>
                <td style="font-weight: 400">Cần đủ chữ hoa, chữ thường, số và ký tự đặc biệt, tối thiểu 6 ký tự.</td>
            </tr>
            <tr>
                <td>"Mật khẩu mới trùng với một trong 5 mật khẩu đã dùng gần đây"</td>
                <td style="font-weight: 400">Chọn mật khẩu chưa từng dùng trong 5 lần đổi gần nhất.</td>
            </tr>
            <tr>
                <td>"Mật khẩu hiện tại không đúng." khi đổi mật khẩu</td>
                <td style="font-weight: 400">Ô Mật khẩu hiện tại phải là mật khẩu đang dùng (hoặc mật khẩu tạm vừa được cấp).</td>
            </tr>
            <tr>
                <td>"Phiên làm việc đã hết hạn do không hoạt động..." / "Vui Lòng Đăng Nhập!"</td>
                <td style="font-weight: 400">Hết phiên, đăng nhập lại. Phần nhập dở chưa lưu sẽ mất.</td>
            </tr>
            <tr>
                <td>"Mật khẩu xác nhận không đúng. Thao tác ... đã bị huỷ..."</td>
                <td style="font-weight: 400">Gõ lại mật khẩu đăng nhập của bạn ở ô Mật Khẩu Xác Nhận rồi ký lại. Không có gì bị thay đổi
                    ở lần ký sai.</td>
            </tr>
            <tr>
                <td>Trang chủ không hiện phiếu đáng lẽ chờ mình ký</td>
                <td style="font-weight: 400">Vai trò ký của bạn chưa được gán cho phòng của phiếu, hoặc phiếu chưa tới bước của bạn. Xem
                    <a href="{{ route('pages.userGuide.show', 'permission') }}#thiet-lap">Thiết Lập Phân Quyền</a>.</td>
            </tr>
            <tr>
                <td>Bấm phiếu trên Trang chủ thì phòng ban đang chọn bị đổi</td>
                <td style="font-weight: 400">Đúng thiết kế: phiếu thuộc phòng khác nên hệ thống tự chuyển sang phòng của phiếu để ký. Đổi
                    lại phòng ở ô cuối cột icon.</td>
            </tr>
            <tr>
                <td>Không thấy menu Lịch Sử Đăng Nhập › Toàn Hệ Thống hoặc Audit Trail</td>
                <td style="font-weight: 400">Cần quyền xem tương ứng. Mục <b>Của Tôi</b> thì ai cũng xem được.</td>
            </tr>
        </tbody>
    </table>
</div>
