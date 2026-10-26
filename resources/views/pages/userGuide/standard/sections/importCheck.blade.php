<p>Tab đầu tiên của màn Nhập: tạo phiếu nhập và kiểm tra các <b>ống chuẩn</b> vừa nhập. Ống mới luôn ở trạng thái
    <span class="badge badge-warning">Chờ kiểm tra</span>, nằm trong khu Biệt Trữ và <b>chưa cộng vào tồn</b>.</p>

<h3>Nhập ống chuẩn</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Nhập chất chuẩn</span>.</li>
    <li>Chọn <b>Chất Chuẩn</b> (chỉ chuẩn phòng đã khai). Ô <b>Mã Ống Chuẩn (Dự kiến)</b> hiện mã sẽ được cấp.</li>
    <li>Nhập <b>Lượng / Ống</b>. Nhập nhiều ống giống nhau thì tăng <b>Số Lần Nhập</b> (tối đa 50), mỗi ống một mã.</li>
    <li>Điền thông tin lô và chọn <b>loại hạn dùng</b> (xem bảng dưới), đính kèm CoA, rồi <b>Lưu</b>. Ngày Nhập và Người Nhập do
        hệ thống tự ghi, không sửa được.</li>
</ol>

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Chỉ Tiêu Kiểm', false, 'Một hoặc nhiều chỉ tiêu ống chuẩn này dùng cho.'],
    ['Số Lô, Hàm Lượng, Độ Ẩm (%), Số CoA', false, 'Theo phiếu kiểm nghiệm gốc của nhà sản xuất.'],
    ['Loại hạn dùng', true, '<b>Hạn dùng xác định</b> / <b>Chưa xác định (Check online)</b> / <b>Cần Retest định kỳ</b> / <b>Cần xác định lại hạn dùng nội bộ</b>. Trừ Check online, các loại còn lại bắt buộc nhập ngày hạn (hạn NSX hoặc hạn retest NSX); nút <b>Gợi ý</b> tính hạn theo danh mục của phòng.'],
    ['Chuẩn có cần kiểm soát khối lượng', false, 'Tick nếu cần cân bì theo dõi lượng chuẩn lấy ra; khi đó chọn <b>Dạng Chuẩn</b>: Dạng Bột Rời / Dạng Bột Mịn / Dạng Sệt. Xem <a href="#khoi-luong">Kiểm soát khối lượng</a>.'],
    ['Chuẩn cần chiết ống trước khi sử dụng', false, 'Đánh dấu để người dùng biết phải chiết ống trước khi dùng.'],
    ['Lô đánh giá hạn dùng', false, 'Chỉ ống được tick mới lập được phiếu ở tab <a href="#danh-gia">Đánh giá hạn dùng CTC</a> của Tồn Kho Chất Chuẩn.'],
    ['Định Khu Tạm (Biệt Trữ)', false, 'Chỉ chọn được vị trí có phân loại Biệt Trữ.'],
    ['Đính Kèm File Hồ Sơ (CoA...)', false, 'Nhiều file, tối đa 10MB mỗi file.'],
]])

<h3>Xác nhận kiểm tra</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-clipboard-check mr-1"></i> Kiểm tra</span> trên ống đang chờ.</li>
    <li><b>Đạt</b>: bắt buộc chọn <b>định khu vị trí lưu trữ thật</b>, bổ sung thông tin còn thiếu. Ống vào tồn và dùng được.</li>
    <li><b>Không đạt</b>: bắt buộc ghi kết luận. Ống coi như trả hàng, không bao giờ vào tồn.</li>
</ol>
<p class="mb-0">Kết quả kiểm tra là quyết định cuối, không kiểm tra lại được. Ống đã kiểm tra rời khỏi tab này và xem ở
    <a href="#so-nhap">Sổ nhập chất chuẩn</a>. Chuẩn nhận từ phòng khác được kiểm tra ngay lúc <a href="#lien-phong-ban">Nhận</a>.</p>
