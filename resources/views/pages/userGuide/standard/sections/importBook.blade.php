<p>Ghi nhận từng <b>ống chuẩn</b> nhập vào kho của phòng. Ống mới luôn ở trạng thái
    <span class="badge badge-warning">Chờ kiểm tra</span>, nằm trong khu Biệt Trữ và chưa cộng vào tồn.</p>

<h3>Nhập ống chuẩn</h3>
<ol class="ug-steps">
    <li>Ở tab <b>Sổ nhập chất chuẩn</b>, bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Nhập chất chuẩn</span>.</li>
    <li>Chọn <b>Chất Chuẩn</b> (chỉ chuẩn phòng đã khai). Ô <b>Mã Ống Chuẩn (Dự kiến)</b> hiện mã sẽ được cấp.</li>
    <li>Nhập <b>Lượng / Ống</b>. Nhập nhiều ống giống nhau thì tăng <b>Số Lần Nhập</b> (tối đa 50), mỗi ống một mã.</li>
    <li>Điền thông tin lô và chọn <b>loại hạn dùng</b> (xem bảng dưới), đính kèm CoA, rồi <b>Lưu</b>.</li>
</ol>

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Chỉ Tiêu Kiểm', false, 'Một hoặc nhiều chỉ tiêu ống chuẩn này dùng cho.'],
    ['Số Lô, Hàm Lượng, Độ Ẩm (%), Số CoA', false, 'Theo phiếu kiểm nghiệm gốc của nhà sản xuất.'],
    ['Loại hạn dùng', true, '<b>Hạn dùng xác định</b> / <b>Chưa xác định (Check online)</b> / <b>Cần Retest định kỳ</b> / <b>Cần xác định lại hạn dùng nội bộ</b>. Trừ Check online, các loại còn lại bắt buộc nhập ngày hạn (hạn NSX hoặc hạn retest NSX); nút <b>Gợi ý</b> tính hạn theo danh mục của phòng.'],
    ['Chuẩn có cần kiểm soát khối lượng', false, 'Tick nếu cần cân bì theo dõi lượng chuẩn lấy ra; khi đó chọn <b>Dạng Chuẩn</b>: Dạng Bột Rời / Dạng Bột Mịn / Dạng Sệt. Xem <a href="#khoi-luong">Kiểm soát khối lượng</a>.'],
    ['Chuẩn cần chiết ống trước khi sử dụng', false, 'Đánh dấu để người dùng biết phải chiết ống trước khi dùng.'],
    ['Lô đánh giá hạn dùng', false, 'Chỉ ống được tick mới chọn được ở màn <a href="#danh-gia">Đánh Giá Hạn Dùng</a>.'],
    ['Định Khu Tạm (Biệt Trữ)', false, 'Chỉ chọn được vị trí có phân loại Biệt Trữ.'],
    ['Đính Kèm File Hồ Sơ (CoA...)', false, 'Nhiều file, tối đa 10MB mỗi file.'],
]])

<h3>Các nút trên sổ nhập</h3>
<p class="mb-0"><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> Điều chỉnh (số nhỏ ở góc nút là số lần đã
    sửa, bấm để xem lịch sử) · <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-tag"></i></span> In nhãn ống chuẩn ·
    <span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-lock"></i></span> Khoá phiếu (không xoá cứng, để mã ống không bị cấp lại).</p>
