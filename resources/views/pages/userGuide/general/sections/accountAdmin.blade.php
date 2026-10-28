<p>Phần này dành cho quản trị viên. Cách gán vai trò, phòng ban và quyền riêng xem ở
    <a href="{{ route('pages.userGuide.show', 'permission') }}#thiet-lap">Thiết Lập Phân Quyền</a>; ở đây chỉ nói các thao tác liên quan
    tới bảo mật tài khoản trên màn hình <b>Quản Trị › Phân Quyền › User</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/user-actions.png', 'caption' => 'Cột Reset Pass và Deactive trên màn hình User (khung đỏ)'])

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 220px">Thao tác</th>
                <th>Kết quả</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Tạo tài khoản</td>
                <td style="font-weight: 400">Đặt tên đăng nhập (không trùng toàn hệ thống) và một mật khẩu tạm. Người dùng bị buộc đổi
                    mật khẩu ở lần đăng nhập đầu tiên.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-key"></i></span> Reset mật khẩu</td>
                <td style="font-weight: 400">Nhập <b>Mật Khẩu Tạm</b> và <b>Xác Nhận Mật Khẩu Tạm</b> (đủ độ phức tạp, không trùng 5 mật
                    khẩu gần nhất của người đó). Tài khoản được mở khoá ngay, hạn mật khẩu đặt lại 90 ngày, người dùng phải đổi ở lần
                    đăng nhập kế tiếp. Báo mật khẩu tạm trực tiếp cho đúng người.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> Sửa</td>
                <td style="font-weight: 400">Nếu nhập mật khẩu mới trong hộp thoại Sửa, hệ thống xử lý như reset: mở khoá, buộc đổi ở lần
                    đăng nhập sau. Bỏ trống thì giữ mật khẩu cũ.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-trash"></i></span> Vô hiệu hoá</td>
                <td style="font-weight: 400">Dùng khi nhân viên nghỉ việc, chuyển công tác. Tài khoản không đăng nhập được nữa nhưng
                    không bị xoá: tên trên phiếu cũ, chữ ký và nhật ký vẫn giữ nguyên, tên đăng nhập không cấp lại cho người khác.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Reset mật khẩu</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-key"></i></span> ở cột <b>Reset Pass</b> của người cần reset.</li>
    <li>Nhập <b>Mật Khẩu Tạm</b> và <b>Xác Nhận Mật Khẩu Tạm</b>, bấm <b>Reset Mật Khẩu</b>.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/user-reset.png', 'caption' => 'Hộp thoại Reset Mật Khẩu', 'width' => 340])
    </li>
    <li>Báo mật khẩu tạm cho người dùng; họ đăng nhập bằng mật khẩu này và được yêu cầu <a href="#doi-mat-khau">đổi ngay</a>.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Mọi lần tạo, sửa, reset, vô hiệu hoá tài khoản đều ghi vào <a href="#audit-trail">Audit Trail</a> kèm tên quản trị viên thực
        hiện. Không reset mật khẩu theo yêu cầu qua điện thoại khi chưa xác minh đúng người.</div>
</div>
