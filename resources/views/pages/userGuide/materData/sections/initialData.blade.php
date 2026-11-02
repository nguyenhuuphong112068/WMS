<p>Dùng khi triển khai hệ thống lần đầu. Admin tải các <b>file Excel mẫu</b> theo từng giai đoạn gửi các phòng ban;
    phòng ban điền xong <b>đính kèm file</b> lên màn hình này. Đính kèm chỉ lưu file để Admin xem và tải về, <b>không ghi dữ
    liệu vào hệ thống</b>: Admin tổng hợp file của các phòng, làm sạch dữ liệu rồi tự nạp lên hệ thống.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/khoi-tao-du-lieu.png', 'caption' => 'Màn hình Khởi Tạo Dữ Liệu'])

<h3>Tập hợp dữ liệu theo 4 giai đoạn</h3>
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
<p>Giai đoạn 1 và 2 luôn là một file toàn hệ thống dùng chung cho mọi phòng ban (không chia theo phòng ban, module);
    giai đoạn 2 và 3 làm song song được.</p>

<h3>Các bước (Admin)</h3>
<ol class="ug-steps">
    <li>Ở khối <b>File Excel mẫu</b>, chọn <b>Giai đoạn</b>, <b>Module</b> (Vật tư / Hoá chất / Chất chuẩn) và <b>Phạm vi file</b> (từng phòng ban) rồi bấm
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download mr-1"></i> Tải file mẫu</span>. Có thể kết hợp
        tuỳ ý 3 lựa chọn này.
        <ul class="mb-0 pl-3">
            <li>File một module chỉ gồm sheet dùng chung và sheet của module đó.</li>
            <li>File một phòng ban chỉ điền sẵn tổ, phân loại, định khu của phòng đó; cột Phòng ban chỉ chọn được đúng phòng đó.</li>
        </ul>
    </li>
    <li>Gửi file cho phòng ban. Người điền làm theo sheet <b>Hướng dẫn</b> trong file (mỗi loại file có mục đích, các bước
        điền và lưu ý riêng), điền xong thì đính kèm lên màn hình này.</li>
    <li>Theo dõi khối <b>Theo dõi tiến độ</b> (xem bên dưới). Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download"></i></span>
        để tải file mới nhất của từng việc về, tổng hợp file của tất cả phòng ban trong giai đoạn, làm sạch dữ liệu rồi nạp lên hệ
        thống. Nạp xong giai đoạn trước mới tải file giai đoạn sau.</li>
    <li>Bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-clipboard-check"></i></span> để cập nhật trạng thái file:
        <b>Đã xử lý</b> khi đã nạp (việc chuyển sang Hoàn thành), hoặc <b>Trả lại</b> kèm lý do để phòng ban sửa rồi đính kèm lại.</li>
    <li>Ở giai đoạn 2 - 4, đặt <b>Hạn nộp</b> ở đầu tab rồi bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-save mr-1"></i>Lưu</span>
        (để trống rồi Lưu = không giới hạn). Hết ngày hạn nộp thì phòng ban không đính kèm được nữa; muốn nhận thêm thì gia hạn.</li>
    <li>Phòng không cần làm một việc (VD không dùng chất chuẩn) thì bấm <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-ban mr-1"></i>Không áp dụng</span>
        - việc đó không tính vào tiến độ. Bấm <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-redo mr-1"></i>Áp dụng lại</span>
        để làm lại. Phòng ban cũng tự đánh dấu được việc của phòng mình.</li>
</ol>

<h3>Theo dõi tiến độ</h3>
<p>Mỗi giai đoạn là một tab, số <span class="ug-key">đã xong / tổng</span> trên tab và thanh tiến độ cho biết giai đoạn đã hoàn thành
    bao nhiêu. Trong tab, việc được chia theo <b>công ty / nhà máy</b> (mỗi công ty có thanh tiến độ riêng), rồi nhóm theo phòng ban.
    Bấm nút <b>tên công ty</b> để xem việc của nhà máy đó (mặc định nhà máy đầu tiên, lần sau tự nhớ nhà máy đã chọn), và ô <b>Tìm phòng ban</b> để tìm nhanh theo tên / tên viết tắt.</p>
<table class="table table-bordered ug-table">
    <thead><tr><th>Giai đoạn</th><th>Việc cần làm</th></tr></thead>
    <tbody>
        <tr><td>1. Tổ chức</td><td>1 việc của Admin cho toàn hệ thống (chỉ Admin thấy).</td></tr>
        <tr><td>2. Người dùng &amp; Tổ - Nhóm</td><td>Mỗi phòng ban 1 việc.</td></tr>
        <tr><td>3. Dữ liệu gốc &amp; danh mục, 4. Tồn đầu kỳ</td><td>Mỗi phòng ban có kho 3 việc: Vật tư, Hoá chất, Chất chuẩn.
            File đủ 3 module tính cho cả 3 việc.</td></tr>
    </tbody>
</table>
<table class="table table-bordered ug-table">
    <thead><tr><th>Trạng thái việc</th><th>Ý nghĩa</th></tr></thead>
    <tbody>
        <tr><td><i class="far fa-circle mr-1"></i> Chưa nộp</td><td>Chưa có file đính kèm cho việc này.</td></tr>
        <tr><td><i class="fas fa-hourglass-half text-warning mr-1"></i> Chờ xử lý</td><td>Đã nộp, Admin chưa xử lý.</td></tr>
        <tr><td><i class="fas fa-undo text-danger mr-1"></i> Trả lại</td><td>Admin trả lại kèm lý do - sửa file rồi đính kèm lại.</td></tr>
        <tr><td><i class="fas fa-check-circle text-success mr-1"></i> Hoàn thành</td><td>Admin đã xử lý (đã nạp) file mới nhất.</td></tr>
        <tr><td><i class="fas fa-minus-circle mr-1"></i> Không áp dụng</td><td>Admin hoặc phòng ban đánh dấu phòng không cần làm việc này (VD phòng không dùng module đó).</td></tr>
    </tbody>
</table>
<p>Mỗi việc ghi file đang nộp (lần nộp thứ mấy, người nộp, giờ nộp, tên file) và ghi chú của Admin. Bấm
    <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-history mr-1"></i>2</span> (số lần đã nộp) để mở
    <b>lịch sử nộp</b> ngay dưới việc: mọi lần nộp kèm trạng thái, người / giờ nộp, ghi chú, ghi chú của Admin và nút tải
    <i class="fas fa-download"></i> từng file. Bật <b>Chỉ hiện việc chưa xong</b> để ẩn việc đã hoàn thành / không áp dụng.</p>
<p>Giai đoạn 2 - 4 có dòng <b>Hạn nộp</b> ở đầu tab: <span class="badge badge-info">Còn N ngày</span>,
    <span class="badge badge-warning">Hết hạn cuối ngày hôm nay</span> hoặc <span class="badge badge-danger">Đã hết hạn - không đính kèm được nữa</span>
    (nút Đính kèm / Nộp lại bị khoá).</p>

<h3>Đính kèm file đã điền (phòng ban)</h3>
<ol class="ug-steps">
    <li>Cần file mẫu thì tự tải ở khối <b>File Excel mẫu</b>: chọn <b>Giai đoạn</b> (2 - 4), <b>Module</b> và phòng ban của mình
        (giai đoạn 2 là file chung, không chọn module / phòng ban) rồi bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-download mr-1"></i> Tải file mẫu</span>.
        Chỉ Admin tải được file giai đoạn 1, cả 4 giai đoạn, cả 3 module hoặc toàn hệ thống.</li>
    <li>Ở khối <b>Theo dõi tiến độ</b>, mở tab giai đoạn, bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-upload mr-1"></i>Đính kèm</span>
        ở đúng việc của phòng mình - hộp thoại tự điền giai đoạn, module, phòng ban.</li>
    <li>Chọn file <span class="ug-key">.xlsx</span> (tối đa 50MB), ghi chú nếu cần, bấm
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-upload mr-1"></i> Đính kèm</span>.</li>
    <li>Việc chuyển sang <b>Chờ xử lý</b>. Phòng ban chỉ thấy việc và file của phòng mình.</li>
    <li>Cần sửa (bị Trả lại hoặc tự phát hiện sai) thì bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-upload mr-1"></i>Nộp lại</span>:
        file mới thay thế file đang nộp, file cũ tự chuyển sang <span class="badge badge-light">Đã thay thế</span> trong lịch sử nộp.</li>
</ol>
<p>File đã nộp không xoá được; nộp sai thì bấm <b>Nộp lại</b> với file đúng.</p>
<p>Phòng không dùng một module (VD không có chất chuẩn) thì bấm <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-ban mr-1"></i>Không áp dụng</span>
    ở việc đó thay vì nộp file; bấm nhầm thì bấm <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-redo mr-1"></i>Áp dụng lại</span>.</p>

<h3>Lỗi thường gặp khi đính kèm</h3>
<ul>
    <li><i>"File là mẫu giai đoạn ..., khác giai đoạn đã chọn."</i> (tương tự với module, phòng ban): file mẫu tự ghi giai đoạn /
        module / phòng ban lúc tải - chọn lại cho đúng với file.</li>
    <li><i>"File là mẫu phiên bản cũ"</i>: tải file mẫu mới nhất và điền lại.</li>
    <li><i>"File này đã được đính kèm"</i>: cùng nội dung file đang nộp, không cần nộp lại.</li>
    <li><i>"Giai đoạn ... đã hết hạn nộp"</i>: quá hạn nộp của giai đoạn - liên hệ Admin nếu cần gia hạn.</li>
</ul>

<h3>Cách đọc file mẫu</h3>
<ul>
    <li><b>Dòng nền xám</b> ở đầu sheet là dữ liệu đang có trên hệ thống, đã khoá. Điền dữ liệu mới ở các dòng trắng bên dưới.</li>
    <li><b>Sheet bổ sung</b> (tab màu xám): sheet giai đoạn trước mà file cần đến, VD file Tồn đầu kỳ kèm danh mục, khai báo
        hàng của phòng, định khu, tên hàng. Còn thiếu thì <b>bổ sung trực tiếp vào file</b>, dữ liệu bổ sung được nạp lên hệ
        thống cùng file. Riêng Công ty, Phòng ban, Vai trò do Admin khai, không bổ sung trong file của phòng ban.</li>
    <li>Sheet <b>05 Người dùng</b> chia phân quyền theo module: <b>Phân quyền chung</b> (vai trò dùng cho nhiều module như
        Quản Lý Bộ Phận, Người Xem), <b>Phân quyền Vật tư</b>, <b>Phân quyền Hoá chất</b>, <b>Phân quyền Chất chuẩn</b>. Mỗi ô chỉ chọn
        được vai trò đúng module, ghi nhiều vai trò thì cách nhau dấu phẩy; mỗi người cần ít nhất một vai trò. Module của vai trò
        suy theo tên vai trò (chứa "Vật tư", "Hoá chất", "Chuẩn"); vai trò có quyền ở nhiều module xếp vào cột chung.
        Vai trò Admin không cấp qua file.</li>
    <li>Ở sheet danh mục công ty, <b>không nhập Mã DM</b>: điền Tên thì mã tự sinh nối tiếp mã đang có
        (<span class="ug-key">M00001</span>, <span class="ug-key">C00001</span>, <span class="ug-key">S00001</span>).
        Các phòng điền song song có thể trùng mã trong file; khi nạp hệ thống cấp mã thật theo thứ tự nạp, các sheet sau
        trong file vẫn trỏ đúng danh mục đã khai.</li>
</ul>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Không ghi mật khẩu vào file: Admin đặt mật khẩu khởi tạo chung khi nạp, người dùng phải đổi ở lần đăng nhập đầu.</li>
            <li><b>Tồn đầu kỳ của mỗi phòng chỉ nạp được một lần.</b></li>
            <li>Tải file mẫu và cập nhật trạng thái file chỉ dành cho <b>Admin</b>; đính kèm file cần quyền
                <b>Đính Kèm File Khởi Tạo Dữ Liệu</b>.</li>
        </ul>
    </div>
</div>
