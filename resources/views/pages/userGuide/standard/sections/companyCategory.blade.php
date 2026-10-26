<p>Mô tả <b>bản chất</b> của chất chuẩn, dùng chung toàn công ty. Các phòng chỉ khai và nhập được chất chuẩn đã có ở đây và
    <b>Đã duyệt</b>.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên chất chuẩn', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Chuẩn</b>.'],
    ['Số CAS', false, 'Tối đa 50 ký tự.'],
    ['Nguồn gốc / Nhà sản xuất', true, 'Chọn từ Dữ Liệu Gốc › Nhà Sản Xuất.'],
    ['Phân nhóm chuẩn', true, 'Tick <b>ít nhất một</b> nhóm (Chuẩn Chính, Chuẩn Tạp, Chuẩn Thứ Cấp...). Nhóm quyết định mã ống chuẩn lúc nhập; chuẩn chưa có nhóm thì không nhập kho được.'],
    ['Tỷ trọng d (g/ml)', false, 'Dùng khi quy đổi khối lượng và thể tích.'],
    ['Hạn dùng mặc định (tháng)', false, 'Số tháng dùng được sau khi mở ống. Có khai thì mỗi ống phải xác định hạn nội bộ trước khi dùng.'],
    ['Điều kiện bảo quản', false, 'Áp dụng cho mọi phòng, phòng không khai riêng.'],
    ['Thời gian đặt hàng', false, 'Số ngày từ lúc đặt hàng tới lúc hàng về.'],
    ['Số tài liệu, Ghi chú', false, ''],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>; duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt. Sửa và Khoá
        bắt buộc ghi lý do. <b>Đơn vị tính</b> khai ở tab Chất chuẩn của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
