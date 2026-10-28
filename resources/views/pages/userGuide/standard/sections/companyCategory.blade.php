<p>Mô tả <b>bản chất</b> của chất chuẩn, dùng chung toàn công ty. Các phòng chỉ khai và nhập được chất chuẩn đã có ở đây và
    <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › Chất Chuẩn › tab Danh mục chất chuẩn công ty'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Chất Chuẩn Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên chất chuẩn', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Chuẩn</b>.'],
    ['Số CAS', false, 'Tối đa 50 ký tự.'],
    ['Nguồn gốc / Nhà sản xuất', true, 'Chọn từ Dữ Liệu Gốc › Nhà Sản Xuất.'],
    ['Phân nhóm chuẩn', true, 'Tick <b>ít nhất một</b> nhóm (Chuẩn Chính, Chuẩn Tạp, Chuẩn Thứ Cấp...). Nhóm quyết định mã ống chuẩn lúc nhập; chuẩn chưa có nhóm thì không nhập kho được.'],
    ['Tỷ trọng d (g/ml)', false, 'Dùng khi quy đổi khối lượng và thể tích.'],
    ['Hạn dùng mặc định (tháng)', false, 'Số tháng dùng được sau khi mở ống. Có khai thì mỗi ống phải xác định hạn nội bộ trước khi dùng.'],
    ['Điều kiện bảo quản', false, 'Áp dụng cho mọi phòng, phòng không khai riêng.'],
    ['Thời gian đặt hàng', false, 'Số ngày từ lúc đặt hàng tới lúc hàng về. Chỉ khai ở hộp thoại <b>Thêm mới</b>; sau đó sửa bằng nút riêng ở cột Thời Gian Đặt Hàng (xem bên dưới).'],
    ['Số tài liệu, Ghi chú', false, ''],
]])

<h3>Điều chỉnh thời gian đặt hàng</h3>
<p>Hộp thoại <b>Cập nhật</b> không có ô Thời Gian Đặt Hàng. Người có quyền <span class="ug-key">category_standard_lead_time</span>
    (<b>Điều Chỉnh Thời Gian Đặt Hàng</b>) thấy nút <span class="ug-key"><i class="fas fa-pen"></i></span> ngay trong cột
    <b>Thời Gian Đặt Hàng</b> của bảng - không cần quyền Sửa danh mục.</p>
<ol>
    <li>Bấm nút <span class="ug-key"><i class="fas fa-pen"></i></span> ở dòng cần đổi.</li>
    <li>Nhập số ngày mới (số nguyên, để trống nếu chưa xác định) và <b>Lý Do Điều Chỉnh</b> (bắt buộc).</li>
    <li>Bấm <b>Lưu thay đổi</b>. Bản ghi <b>giữ nguyên trạng thái duyệt</b>, thay đổi được ghi vào Lịch sử thay đổi và Audit Trail.</li>
</ol>
<p><b>Mã mới chờ khai:</b> mỗi khi thêm mã mới, người có quyền trên nhận thẻ <b>Mã mới chờ khai thời gian đặt hàng</b> ở Trang chủ
    (kèm dòng ở thanh Thông báo), cột Thời Gian Đặt Hàng của mã đó có nhãn <span class="badge badge-warning">Chờ khai</span>. Bấm dòng trên thẻ sẽ mở
    màn hình danh mục với hộp thoại điều chỉnh mở sẵn. Lưu (kể cả giữ nguyên số ngày người tạo đã nhập - coi như xác nhận) thì thẻ và nhãn biến mất.
    Người tạo mã đã có quyền này và nhập sẵn số ngày thì không phát sinh thông báo.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>; duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt. Sửa và Khoá
        bắt buộc ghi lý do. <b>Đơn vị tính</b> khai ở tab Chất chuẩn của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
