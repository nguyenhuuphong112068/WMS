<p>Dùng khi triển khai hệ thống lần đầu: điền <b>toàn bộ dữ liệu ban đầu</b> vào một file Excel mẫu rồi nạp một lần.
    File gồm nhiều sheet liên kết, chia theo nhóm: <i>Tổ chức</i>, <i>Dữ liệu gốc dùng chung</i>,
    <i>Tên vật tư / hoá chất / chất chuẩn</i>, <i>Danh mục công ty</i>, <i>Định khu kho</i>,
    <i>Khai báo hàng của phòng</i>, <i>Tồn đầu kỳ</i>.</p>

<h3>Các bước</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download mr-1"></i> Tải file mẫu</span>. Luôn tải
        bản mới nhất, vì danh sách vai trò, đơn vị và hoạt chất trong file lấy theo dữ liệu đang có.</li>
    <li>Điền file theo sheet <b>hướng dẫn</b> có sẵn trong file. Dùng ô chọn sẵn (dropdown) thay vì gõ tay để tránh sai tên.</li>
    <li>Chọn file đã điền (<span class="ug-key">.xlsx</span>, tối đa 50MB). Nếu file có tạo tài khoản mới, nhập
        <b>Mật khẩu khởi tạo tài khoản mới</b>: tối thiểu 6 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt. Người dùng
        phải đổi mật khẩu này ở lần đăng nhập đầu.</li>
    <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clipboard-check mr-1"></i> Kiểm tra</span>.
        Hệ thống chạy toàn bộ kiểm tra, <b>không ghi dữ liệu</b>, rồi liệt kê lỗi và cảnh báo theo từng sheet, từng dòng.</li>
    <li>Sửa hết lỗi trong file, kiểm tra lại tới khi đạt.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-database mr-1"></i> Nạp vào hệ thống</span> và xác nhận.</li>
</ol>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Dữ liệu chỉ được ghi khi <b>toàn bộ file không còn lỗi</b>. Còn một lỗi là không ghi dòng nào.</li>
            <li><b>Tồn đầu kỳ của mỗi phòng chỉ nạp được một lần.</b></li>
            <li>Chỉ tài khoản <b>Admin</b> dùng được màn hình này.</li>
        </ul>
    </div>
</div>
