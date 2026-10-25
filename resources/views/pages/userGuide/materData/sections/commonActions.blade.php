<p>Mọi màn hình Dữ Liệu Gốc dùng chung một bố cục: nút <b>Thêm mới</b> phía trên, bảng dữ liệu bên dưới và cụm nút
    thao tác ở cột cuối của từng dòng.</p>

<h3>Thêm mới</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Thêm mới</span> ở góc trên bảng.</li>
    <li>Điền các ô trong cửa sổ hiện ra. Ô có dấu <span class="ug-req">*</span> là bắt buộc.</li>
    <li>Bấm <b>Lưu</b>. Nếu có lỗi, cửa sổ vẫn mở và báo lỗi màu đỏ ngay dưới ô nhập sai, dữ liệu đã gõ được giữ lại.</li>
</ol>
<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Người tạo và ngày tạo do hệ thống tự ghi theo tài khoản đang đăng nhập, không cần nhập.</div>
</div>

<h3>Các nút trên từng dòng</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 130px">Nút</th>
                <th>Tác dụng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> Sửa</td>
                <td>Mở cửa sổ cập nhật. Bắt buộc nhập <b>Lý do điều chỉnh</b>. Nếu không đổi thông tin nào, hệ thống báo
                    <i>"Chưa có thông tin nào thay đổi nên không lưu"</i>.
                    Ở màn hình có bước duyệt, sửa bản ghi <b>Đã duyệt</b> sẽ đưa bản ghi về <b>Chờ duyệt</b> để duyệt lại.</td>
            </tr>
            <tr>
                <td><span class="badge badge-pill badge-danger">3</span> Số lần sửa</td>
                <td>Số nhỏ ở góc nút Sửa là số lần bản ghi đã thay đổi. Bấm vào để xem <b>lịch sử</b>: ai sửa, lúc nào,
                    đổi từ giá trị nào sang giá trị nào và lý do.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-success ug-ui"><i class="fas fa-check"></i></span> Duyệt</td>
                <td>Chấp nhận bản ghi; từ lúc này bản ghi được chọn ở các màn hình nghiệp vụ. Hệ thống ghi người duyệt và
                    thời gian duyệt.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-times"></i></span> Từ chối</td>
                <td>Đánh dấu bản ghi không được dùng. Có thể sửa lại cho đúng, bản ghi sẽ quay về Chờ duyệt.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-lock"></i></span> Khoá</td>
                <td>Ngừng sử dụng: bản ghi không còn hiện trong các ô chọn của nghiệp vụ, nhưng dữ liệu cũ đã dùng vẫn
                    giữ nguyên. Bắt buộc nhập lý do.</td>
            </tr>
            <tr>
                <td><span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-unlock"></i></span> Mở khoá</td>
                <td>Cho dùng lại bản ghi đã khoá. Bắt buộc nhập lý do.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        Dữ liệu gốc <b>không xoá được</b>, vì phiếu nhập, xuất, tồn kho đều đang trỏ vào. Muốn ngừng dùng thì
        <b>Khoá</b>. Riêng màn hình Định Khu cho xoá cấp kho/kệ/cột/tầng/vị trí khi cấp đó chưa có cấp con nào bên dưới.
    </div>
</div>

<h3>Cột trạng thái trên bảng</h3>
<ul>
    <li><b>Duyệt</b>: <span class="ug-chip is-warn">Chờ duyệt</span> <span class="ug-chip is-success">Đã duyệt</span>
        <span class="ug-chip is-danger">Từ chối</span>, kèm tên người duyệt và thời gian duyệt.</li>
    <li><b>Sử Dụng</b>: <span class="badge badge-success">Hoạt động</span> hoặc <span class="badge badge-danger">Đã khoá</span>.</li>
</ul>
<p class="mb-0">Một bản ghi chỉ được chọn ở nghiệp vụ khi vừa <b>Đã duyệt</b> vừa <b>Hoạt động</b>.</p>

<h3>Tìm kiếm, sắp xếp, phân trang</h3>
<p class="mb-0">Gõ vào ô <b>Tìm kiếm</b> phía trên bảng để lọc theo bất kỳ cột nào. Bấm vào tiêu đề cột để sắp xếp
    tăng hoặc giảm. Chọn số dòng mỗi trang ở ô <b>Hiển thị</b>.</p>
