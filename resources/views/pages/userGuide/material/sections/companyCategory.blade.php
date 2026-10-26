<p>Mô tả <b>bản chất</b> của vật tư, dùng chung cho toàn công ty. Mỗi dòng là một tổ hợp <b>Tên vật tư + Nhà sản
    xuất</b>. Các phòng chỉ khai và dùng được vật tư đã có ở đây và đã <b>Đã duyệt</b>.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Vật Tư', false, 'Hệ thống tự sinh khi lưu, dạng <span class="ug-key">M00001</span>.'],
    ['Tên Vật Tư', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Vật Tư</b> (đã duyệt).'],
    ['Nhà Sản Xuất', true, 'Chọn từ <b>Dữ Liệu Gốc › Nhà Sản Xuất</b>.'],
    ['Thông Tin Kỹ Thuật', false, 'Quy cách, kích thước, tiêu chuẩn. Tối đa 100 ký tự.'],
    ['Part Number', false, 'Mã P/N của hãng. Tối đa 100 ký tự.'],
    ['Phân Loại Vật Tư', true, 'Chọn đủ 4 tiêu chí: <b>Theo Giá Trị</b> (Thấp/Cao), <b>Theo Mức Độ Quan Trọng</b> (Thấp/Cao), <b>Theo Hàng Hoá Nguy Hiểm</b> (Có/Không), <b>Theo Cần Hiệu Chuẩn Trước Khi Dùng</b> (Có/Không).'],
    ['Bộ Phận Mua Hàng', true, '<b>Cung Ứng</b> hoặc <b>Hành Chánh</b>. Quyết định vật tư đi đường nào khi thiếu: Cung Ứng → lập phiếu dự trù; Hành Chánh → gửi đề nghị VPP.'],
    ['Thời Gian Đặt Hàng', false, 'Số ngày từ lúc đặt hàng tới lúc hàng về công ty.'],
]])

<h3>Phân loại ảnh hưởng tới đâu?</h3>
<ul>
    <li><b>Trình ký đề nghị cấp phát</b>: các tiêu chí là điều kiện để xác định vai trò nào phải ký
        (Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư).</li>
    <li><b>Cần hiệu chuẩn trước khi dùng = Có</b>: vật tư phải qua bước <a href="#hieu-chuan">Hiệu chuẩn</a> mới cấp phát được.</li>
    <li><b>Bộ phận mua hàng = Hành Chánh</b>: chỉ vật tư loại này mới được đưa vào <a href="#de-nghi-vpp">Đề nghị VPP</a>.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>. Duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt.
        Sửa và Khoá bắt buộc ghi lý do điều chỉnh. <b>Đơn vị tính</b> không khai ở đây mà khai ở tab Vật tư của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
