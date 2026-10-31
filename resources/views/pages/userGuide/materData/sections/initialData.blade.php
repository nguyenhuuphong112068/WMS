<p>Dùng khi triển khai hệ thống lần đầu: điền <b>toàn bộ dữ liệu ban đầu</b> vào một file Excel mẫu rồi nạp một lần.
    File gồm nhiều sheet liên kết, chia theo nhóm: <i>Tổ chức</i>, <i>Dữ liệu gốc dùng chung</i>,
    <i>Tên vật tư / hoá chất / chất chuẩn</i>, <i>Danh mục công ty</i>, <i>Định khu kho</i>,
    <i>Khai báo hàng của phòng</i>, <i>Tồn đầu kỳ</i>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/khoi-tao-du-lieu.png', 'caption' => 'Màn hình Khởi Tạo Dữ Liệu'])

<h3>Các bước</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download mr-1"></i> Tải file mẫu</span>. Luôn tải
        bản mới nhất: file điền sẵn dữ liệu đang có trong hệ thống (công ty, phòng ban, dữ liệu gốc, tên hàng, danh mục
        công ty, định khu) ở các <b>dòng nền xám</b> đầu mỗi sheet. Các dòng này đã khoá, chỉ dùng làm nguồn chọn cho sheet sau.</li>
    <li>Điền dữ liệu mới vào các dòng trắng bên dưới theo sheet <b>hướng dẫn</b> có sẵn trong file. Dùng ô chọn sẵn
        (dropdown) thay vì gõ tay để tránh sai tên.</li>
    <li>Ở 3 sheet danh mục công ty, <b>không nhập Mã DM</b>: điền Tên thì mã tự sinh nối tiếp mã đang có
        (<span class="ug-key">M00001</span>, <span class="ug-key">C00001</span>, <span class="ug-key">S00001</span>).
        Nếu mã đã bị dùng trong lúc bạn điền file, lúc nạp hệ thống cấp mã kế tiếp và báo ở phần cảnh báo.</li>
    <li>Chọn file đã điền (<span class="ug-key">.xlsx</span>, tối đa 50MB). Nếu file có tạo tài khoản mới, nhập
        <b>Mật khẩu khởi tạo tài khoản mới</b>: tối thiểu 6 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt. Người dùng
        phải đổi mật khẩu này ở lần đăng nhập đầu.</li>
    <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clipboard-check mr-1"></i> Kiểm tra</span>.
        Hệ thống chạy toàn bộ kiểm tra, <b>không ghi dữ liệu</b>, rồi liệt kê lỗi và cảnh báo theo từng sheet, từng dòng.</li>
    <li>Sửa hết lỗi trong file, kiểm tra lại tới khi đạt.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-database mr-1"></i> Nạp vào hệ thống</span> và xác nhận.</li>
</ol>

<h3>Tập hợp dữ liệu theo 4 giai đoạn</h3>
<p>Chọn <b>Giai đoạn</b> khi tải file mẫu. Nạp xong giai đoạn trước mới tải file giai đoạn sau, vì file giai đoạn sau lấy
    dữ liệu giai đoạn trước theo hệ thống lúc tải.</p>
<table class="table table-bordered ug-table">
    <thead><tr><th>Giai đoạn</th><th>Người điền</th><th>Sheet</th></tr></thead>
    <tbody>
        <tr><td>1. Tổ chức</td><td>Admin</td><td>01 Công ty, 02 Phòng ban, 04 Vai trò</td></tr>
        <tr><td>2. Người dùng &amp; Tổ - Nhóm</td><td>Phòng ban</td><td>03 Tổ - Nhóm (bổ sung tổ còn thiếu), 05 Người dùng
            (đăng nhập bằng <b>mã số nhân viên</b>)</td></tr>
        <tr><td>3. Dữ liệu gốc &amp; danh mục</td><td>Phòng ban</td><td>06 - 18 (dữ liệu gốc, tên hàng), 19 - 21 (danh mục công ty),
            22 - 26 (định khu), 27 - 29 (hàng của phòng), 30 (quy đổi đơn vị)</td></tr>
        <tr><td>4. Tồn đầu kỳ</td><td>Phòng ban</td><td>31 - 33</td></tr>
    </tbody>
</table>
<p>File giai đoạn 2 - 4 chỉ hiện sheet cần điền; các sheet giai đoạn trước cần làm danh sách chọn (phòng ban, vai trò, tổ;
    ở giai đoạn 4 thêm danh mục, vị trí, nhà cung cấp...) được kèm theo dạng ẩn. Giai đoạn 1 và 2 luôn là một file toàn hệ thống
    dùng chung cho mọi phòng ban (không chia theo phòng ban, module); giai đoạn 2 và 3 làm song song được.</p>

<h3>Nhiều phòng ban / module điền cùng lúc</h3>
<ol class="ug-steps">
    <li>Admin nạp trước file giai đoạn 1 gồm công ty, phòng ban, vai trò.</li>
    <li>Chọn <b>Giai đoạn</b>, <b>Module</b> (Vật tư / Hoá chất / Chất chuẩn) và <b>Phạm vi file</b> (từng phòng ban) rồi bấm
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download mr-1"></i> Tải file mẫu</span>, gửi đúng người
        phụ trách. Có thể kết hợp tuỳ ý 3 lựa chọn này.
        <ul class="mb-0 pl-3">
            <li>File một module chỉ gồm sheet dùng chung (tổ chức, đơn vị, nhà sản xuất, sản phẩm, định khu, quy đổi) và sheet
                của module đó (tên hàng, danh mục công ty, khai báo cho phòng, tồn đầu kỳ).</li>
            <li>File một phòng ban chỉ điền sẵn tổ, phân loại, định khu của phòng đó; cột Phòng ban chỉ chọn được đúng phòng đó;
                không có các sheet Công ty, Phòng ban, Vai trò (Admin khai ở file toàn hệ thống).</li>
        </ul>
    </li>
    <li>Nhận file về, <b>kiểm tra và nạp lần lượt từng file</b>. Hệ thống chỉ cho một lượt nạp chạy tại một thời điểm.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Các phòng điền song song nên Mã DM trong các file có thể trùng nhau (cùng bắt đầu từ mã kế tiếp). Khi nạp, hệ thống
        cấp mã thật theo thứ tự nạp và báo ở phần cảnh báo; các sheet sau trong file vẫn trỏ đúng danh mục phòng đã khai.
        Tên hàng / danh mục mà phòng nạp trước đã thêm thì phòng nạp sau được dùng lại, không tạo trùng.</div>
</div>

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
