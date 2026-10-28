<p>Người trong tổ chỉ được thấy và dùng những ống chuẩn đã được <b>cấp phát cho tổ mình</b>. Vì vậy tổ phải đề nghị trước, kho
    cấp ống cho tổ, rồi mới <a href="#su-dung-chuan">ghi sử dụng</a>. Đề nghị chuẩn nội bộ <b>không có bước ký duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/de-nghi-noi-bo.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Chất Chuẩn › tab Đề nghị cấp phát chuẩn nội bộ'])

<p>Danh sách đề nghị chia 3 tab con, số trên mỗi tab là số phiếu đang có:</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 160px;">Tab con</th><th>Gồm các phiếu</th></tr>
        </thead>
        <tbody>
            <tr><td><b>Chưa gửi</b></td><td>Đang <b>Lưu tạm</b>, tổ còn sửa / huỷ được. Hiện đủ, không lọc ngày.</td></tr>
            <tr><td><b>Chờ xử lý</b></td><td><b>Chờ cấp phát</b> (chưa cấp mục nào) hoặc <b>Cấp một phần</b>. Hiện đủ, không lọc ngày. Mở trang mặc định vào tab này.</td></tr>
            <tr><td><b>Đã xử lý</b></td><td>Không còn mục chờ cấp: <b>Đã cấp đủ</b>, <b>Từ chối</b> (từ chối hết các mục) hoặc <b>Đã huỷ</b>. Lọc theo ngày lập (mặc định 30 ngày gần nhất).</td></tr>
        </tbody>
    </table>
</div>
<p>Lưu tạm xong hệ thống mở tab <b>Chưa gửi</b>, gửi đề nghị xong mở tab <b>Chờ xử lý</b>. Phiếu vừa cấp / từ chối xong mục cuối trong
    modal chi tiết thì số đếm chuyển sang tab <b>Đã xử lý</b> ngay, dòng của phiếu chuyển tab ở lần tải trang sau.</p>

<h3>Tổ lập đề nghị</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Tạo đề nghị cấp phát chuẩn</span>.</li>
    <li>Thêm từng dòng: chất chuẩn, số lượng đề nghị, đơn vị, tên sản phẩm, chỉ tiêu, kiểm nghiệm viên, ghi chú; nhập ngày mong muốn.
        Ô <b>Chất Chuẩn</b> chỉ gồm chuẩn phòng đã khai ở <a href="#chuan-cua-phong">Chất Chuẩn Của Phòng</a> (kho phòng chỉ có ống
        của các chuẩn này); gõ mã, tên hoặc nhà sản xuất để tìm, mỗi kết quả hiện nhà sản xuất ngay dưới tên để phân biệt các chuẩn
        trùng tên. Hoặc bấm <span class="btn btn-sm btn-outline-info ug-ui"><i class="fas fa-boxes-stacked mr-1"></i> Danh mục chuẩn tồn của phòng</span>
        để tick nhiều chuẩn cùng lúc.
        Ô <b>Kiểm Nghiệm Viên</b> chỉ liệt kê người thuộc bộ phận đang chọn (phòng ban chính hoặc thành viên một tổ của phòng),
        gõ tên / mã nhân viên để tìm nhanh.</li>
    <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu</span> để giữ lại sửa tiếp, hoặc
        <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</span> để chuyển cho kho.</li>
</ol>

<h3>Kho cấp phát cho tổ</h3>
<ol class="ug-steps">
    <li>Thủ kho / quản lý kho mở đề nghị, với từng dòng chọn <b>mã ống chuẩn</b> trong kho và số lượng cấp, ghi chú khi giao ống.</li>
    <li>Đánh dấu <b>Trả lại chuẩn</b> nếu tổ phải trả ống về sau khi dùng. Khi ghi sử dụng, ống này hiện nhãn nhắc trả.</li>
    <li>Bấm <span class="btn btn-xs btn-success ug-ui"><i class="fas fa-check-circle mr-1"></i> Cấp phát</span> hoặc
        <span class="btn btn-xs btn-danger ug-ui"><i class="fas fa-ban mr-1"></i> Từ chối</span> trên từng dòng. Hai nút chạy ngầm:
        trang không tải lại, phiếu vẫn mở, chỉ dòng vừa xử lý và trạng thái phiếu được cập nhật ngay để làm tiếp các dòng khác.
        Thời điểm cấp phát là lúc bấm, không sửa được. Có thể lưu nháp thông tin cấp cho cả phiếu rồi cấp sau.</li>
    <li>Khi <b>ĐVT</b> cấp phát trùng đơn vị của ống chuẩn, <b>SL Cấp Phát</b> không được lớn hơn tồn còn lại của ống
        (tồn hiện ngay dưới mã ống đã chọn); vượt thì hệ thống báo
        <i>"Ống chuẩn … chỉ còn …, không đủ để cấp phát …!"</i> và không cấp. Khác đơn vị thì không so được nên không chặn.</li>
    <li>Cấp phát xong, <b>kiểm nghiệm viên (KNV)</b> của dòng đó thấy ống vừa cấp ở Trang chủ, thẻ
        <span class="ug-key">Chuẩn đã cấp cho bạn</span> (nhóm Đề nghị nội bộ, nhãn <b>Cấp cho bạn</b>): mã ống, tên chuẩn, số lượng,
        mã phiếu. Bấm vào thẻ / dòng để mở tab <a href="#su-dung-chuan">Sử dụng chuẩn</a>, dòng ống đó được tô sáng (phiếu ở phòng khác thì hệ thống
        tự chuyển phòng). Thẻ tự hết khi KNV lập
        phiếu sử dụng từ ống đó.</li>
    <li>Chất chuẩn không còn ống nào cấp phát được thì dòng đó hiện <b class="text-danger">Không có ống chuẩn cấp phát được</b> và
        không có nút Cấp phát, chỉ còn Từ chối.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Cấp phát cho tổ <b>chưa trừ tồn</b>: tồn chỉ bị trừ khi tổ ghi phiếu sử dụng. Không cấp được ống <b>hết hạn</b> hoặc ống
        <b>chưa xác định hạn dùng nội bộ</b> (với chuẩn có hạn dùng mặc định). Đề nghị cấp hết thì chuyển trạng thái hoàn tất, cấp một phần
        thì chờ cấp tiếp.</div>
</div>
