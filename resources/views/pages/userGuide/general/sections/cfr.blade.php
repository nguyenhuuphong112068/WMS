<p>21 CFR Part 11 là quy định của FDA (Hoa Kỳ) về <b>hồ sơ điện tử</b> và <b>chữ ký điện tử</b> trong môi trường GMP: hồ sơ trên
    máy phải đáng tin cậy như hồ sơ giấy. Bảng dưới tóm tắt yêu cầu chính và cách WMS đáp ứng, để bạn biết vì sao hệ thống có những
    ràng buộc như khoá tài khoản, tự đăng xuất hay bắt nhập lại mật khẩu khi ký.</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 120px">Điều khoản</th>
                <th style="width: 220px">Yêu cầu</th>
                <th>WMS đáp ứng thế nào</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>§11.10(d)</td>
                <td>Chỉ người được phép mới truy cập</td>
                <td style="font-weight: 400">Mỗi người một tài khoản riêng. Quyền theo vai trò và phòng ban, nút và màn hình không có
                    quyền thì không hiện (xem <a href="{{ route('pages.userGuide.show', 'permission') }}#tong-quan">Phân Quyền</a>).
                    Tự đăng xuất sau 15 phút không thao tác.</td>
            </tr>
            <tr>
                <td>§11.10(e)</td>
                <td>Nhật ký truy vết (audit trail) có dấu thời gian, do máy tự ghi</td>
                <td style="font-weight: 400"><a href="#audit-trail">Audit Trail</a> tự ghi người thực hiện, thời điểm, địa chỉ IP, thao
                    tác, giá trị cũ và mới. Nhật ký chỉ ghi thêm, không ai sửa hay xoá được, kể cả quản trị viên.</td>
            </tr>
            <tr>
                <td>§11.10(g)</td>
                <td>Kiểm tra thẩm quyền trước khi ký, thao tác</td>
                <td style="font-weight: 400">Chỉ người có vai trò được giao ở đúng bước mới ký được (ví dụ Quản Lý Bộ Phận, Ban Giám
                    Đốc với dự trù). Kiểm tra lại ở máy chủ khi lưu, không chỉ ẩn nút.</td>
            </tr>
            <tr>
                <td>§11.50</td>
                <td>Chữ ký thể hiện tên người ký, thời điểm và ý nghĩa</td>
                <td style="font-weight: 400">Phiếu đã ký hiện người ký dạng <b>Họ Tên (tên tài khoản)</b>, ngày giờ ký và kết quả
                    (đồng ý / từ chối, kèm lý do).</td>
            </tr>
            <tr>
                <td>§11.100</td>
                <td>Chữ ký là duy nhất của một người, không cấp lại cho người khác</td>
                <td style="font-weight: 400">Tên tài khoản không trùng. Tài khoản người nghỉ việc chỉ bị vô hiệu hoá, không xoá, nên
                    không thể cấp lại cho người khác.</td>
            </tr>
            <tr>
                <td>§11.200(a)</td>
                <td>Chữ ký không sinh trắc học có ít nhất hai thành phần định danh</td>
                <td style="font-weight: 400">Phiên đăng nhập + nhập lại mật khẩu ngay lúc ký.</td>
            </tr>
            <tr>
                <td>§11.300</td>
                <td>Kiểm soát mật khẩu</td>
                <td style="font-weight: 400">Mật khẩu đủ phức tạp, hết hạn sau 90 ngày, không dùng lại 5 mật khẩu gần nhất, mật khẩu tạm
                    phải đổi ở lần đăng nhập đầu, khoá 15 phút sau 5 lần sai, lưu dạng mã hoá một chiều. Mọi lần đăng nhập thất bại
                    được ghi vào <a href="#lich-su-dang-nhap">Lịch Sử Đăng Nhập</a>.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Trách nhiệm của người dùng</h3>
<ol class="ug-steps">
    <li>Giữ bí mật mật khẩu, không dùng chung tài khoản.</li>
    <li>Đăng xuất hoặc khoá máy khi rời chỗ.</li>
    <li>Thấy đăng nhập lạ trong <a href="#lich-su-dang-nhap">Lịch Sử Đăng Nhập Của Tôi</a> thì <a href="#doi-mat-khau">đổi mật khẩu</a>
        ngay và báo quản trị viên.</li>
    <li>Chỉ ký những gì mình đã xem xét; chữ ký điện tử ràng buộc như chữ ký tay.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Các quy trình ngoài phần mềm như thẩm định hệ thống, đào tạo người dùng, cam kết chữ ký điện tử với cơ quan quản lý do quy
        trình chất lượng của công ty quy định, không thuộc hướng dẫn này.</div>
</div>
