<p>Mô tả <b>bản chất</b> của hoá chất, dùng chung toàn công ty. Mỗi dòng gắn <b>một tên hoá chất</b> với <b>một nhà sản xuất</b>.
    Các phòng chỉ khai và dùng được hoá chất đã có ở đây và <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › Hoá Chất › tab Danh mục hoá chất công ty'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Hoá Chất Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Danh Mục', false, 'Hệ thống tự sinh khi lưu.'],
    ['Loại', false, 'Gõ hoặc chọn gợi ý: ' . implode(', ', config('chemical.types')) . '.'],
    ['Tên Hoá Chất', true, 'Chọn từ <b>Dữ Liệu Gốc › Hoá Chất</b>. Một danh mục chỉ mang một tên hoá chất.'],
    ['Nhà Sản Xuất', true, 'Chọn từ <b>Dữ Liệu Gốc › Nhà Sản Xuất</b>.'],
    ['Tỉ Trọng d (g/ml)', false, 'Dùng để quy đổi giữa kg và lít (ví dụ khi đối chiếu ngưỡng PL IV).'],
    ['Điều Kiện Bảo Quản', false, 'Chọn từ Dữ Liệu Gốc.'],
    ['Thời Gian Đặt Hàng', false, 'Số ngày từ lúc đặt hàng tới lúc hàng về công ty. Chỉ khai ở hộp thoại <b>Thêm mới</b>; sau đó sửa bằng nút riêng ở cột Thời Gian Đặt Hàng (xem bên dưới).'],
    ['Phân Loại NĐ 24/2026', false, 'Chỉ hiển thị, hệ thống tự suy từ hoạt chất thành phần. Muốn đổi thì sửa ở Dữ Liệu Gốc.'],
    ['Cảnh Báo An Toàn', false, 'Chọn một hoặc nhiều nhóm GHS (lỏng dễ cháy, ăn mòn, oxy hoá, độc...). In ra dải giữa của nhãn dán lô và dùng để xét tương kỵ.'],
    ['Hàm Lượng Hoạt Chất (%)', false, 'Chỉ có ở form <b>Sửa</b>. Dùng khi đối chiếu ngưỡng PL IV: khối lượng hoạt chất = khối lượng hoá chất × %.'],
    ['Số Hồ Sơ', false, 'Chỉ có ở form Sửa. Tối đa 20 ký tự.'],
]])

<h3>Các cột cần chú ý trên bảng</h3>
<ul>
    <li><b>Phân Loại</b>: các nhóm NĐ 24/2026 suy được. Mã thuộc nhóm 9 (Bảng A) hoặc nhóm 10 (Bảng B) có thêm số liệu đối chiếu
        ngưỡng tồn trữ toàn công ty; bấm vào con số để xem các lô tạo nên nó.</li>
    <li><b>Phòng Ban Đang Dùng</b>: các phòng đã khai hoá chất này ở tab Hoá chất của phòng.</li>
    <li><b>Đã khai báo trên Cổng thông tin quốc gia</b>: bấm nút xác nhận và nhập lại mật khẩu. Chỉ xác nhận được <b>một lần</b>,
        sau đó không sửa hay gỡ được, và không làm danh mục phải duyệt lại.</li>
</ul>

<h3>Điều chỉnh thời gian đặt hàng</h3>
<p>Hộp thoại <b>Cập nhật</b> không có ô Thời Gian Đặt Hàng. Người có quyền <span class="ug-key">category_chemical_lead_time</span>
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
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>; duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt. Sửa và
        Khoá bắt buộc ghi lý do. <b>Đơn vị tính</b> không khai ở đây mà ở tab Hoá chất của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
