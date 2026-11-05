<p>Mô tả <b>bản chất</b> của chất chuẩn, dùng chung toàn công ty. Các phòng chỉ khai và nhập được chất chuẩn đã có ở đây và
    <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › Chất Chuẩn › tab Danh mục chất chuẩn công ty'])

<h3>Thêm nhiều mã một lần</h3>
<p>Hộp thoại <b>Thêm Danh Mục Chất Chuẩn Công Ty Mới</b> là một bảng, <b>mỗi dòng là một mã chất chuẩn</b>. Bấm <b>Lưu</b> một lần là thêm được
    tất cả các dòng.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới</span>, hộp thoại mở sẵn một dòng trống.</li>
    <li>Nếu nhiều mã giống nhau ở vài ô (Nguồn gốc / NSX, Điều kiện bảo quản, Phân nhóm chuẩn): chọn trước ở hàng <b>Điền nhanh cho mọi dòng</b> ngay dưới tiêu đề bảng.
        Giá trị ở đây áp ngay cho mọi dòng đang có và điền sẵn cho dòng thêm sau.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm dòng</span>, hoặc
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clone"></i></span> ở một dòng để nhân bản dòng đó
        (giữ nguồn gốc, tỷ trọng, bảo quản, phân nhóm, ghi chú - chỉ cần chọn lại tên). Nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> bỏ dòng.</li>
    <li>Bấm <b>Lưu</b>. Mã cấp liên tiếp theo thứ tự dòng (cột <b>Mã</b> là mã dự kiến). Dòng để trống hoàn toàn được bỏ qua.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: hộp thoại mở lại với đủ các dòng vừa nhập, lỗi ghi theo
        <b>"Dòng N: ..."</b> ở đầu hộp thoại và dòng lỗi tô nền đỏ nhạt. Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Chất Chuẩn Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã', false, 'Hệ thống tự sinh khi lưu dạng <span class="ug-key">S00001</span>, cấp liên tiếp theo thứ tự dòng.'],
    ['Tên Chất Chuẩn', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Chuẩn</b>. Trùng Tên + Nguồn gốc với mã đang có (hoặc với dòng khác) vẫn thêm được, mã mới lên <b>Version</b> kế tiếp.'],
    ['Số CAS', false, 'Điền sẵn theo tên đã chọn, sửa lại được. Tối đa 50 ký tự.'],
    ['Nguồn Gốc / NSX', true, 'Chọn từ Dữ Liệu Gốc › Nhà Sản Xuất.'],
    ['Tỷ Trọng (g/ml)', false, 'Dùng khi quy đổi khối lượng và thể tích.'],
    ['Điều Kiện Bảo Quản', false, 'Áp dụng cho mọi phòng, phòng không khai riêng.'],
    ['Phân Nhóm Chuẩn', true, 'Chọn <b>1 nhóm</b> (Chuẩn Chính, Chuẩn Tạp, Chuẩn Thứ Cấp...). Nhóm quyết định mã ống chuẩn lúc nhập; chuẩn chưa có nhóm thì không nhập kho được.'],
    ['Ghi Chú', false, 'Tối đa 500 ký tự.'],
]])

<h3>Điều chỉnh thời gian đặt hàng</h3>
<p>Hộp thoại <b>Thêm mới</b> và <b>Cập nhật</b> không có ô Thời Gian Đặt Hàng - số ngày do người có quyền khai sau. Người có quyền <span class="ug-key">category_standard_lead_time</span>
    (<b>Điều Chỉnh Thời Gian Đặt Hàng</b>) thấy nút <span class="ug-key"><i class="fas fa-pen"></i></span> ngay trong cột
    <b>Thời Gian Đặt Hàng</b> của bảng - không cần quyền Sửa danh mục.</p>
<ol>
    <li>Bấm nút <span class="ug-key"><i class="fas fa-pen"></i></span> ở dòng cần đổi.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/danh-muc-thoi-gian-dat-hang.png', 'caption' => 'Hộp thoại Điều Chỉnh Thời Gian Đặt Hàng', 'width' => 420])
    </li>
    <li>Nhập số ngày mới (số nguyên, để trống nếu chưa xác định) và <b>Lý Do Điều Chỉnh</b> (bắt buộc).</li>
    <li>Bấm <b>Lưu thay đổi</b>. Bản ghi <b>giữ nguyên trạng thái duyệt</b>, thay đổi được ghi vào Lịch sử thay đổi và Audit Trail.</li>
</ol>
<p><b>Mã mới chờ khai:</b> mỗi khi thêm mã mới, người có quyền trên nhận thẻ <b>Mã mới chờ khai thời gian đặt hàng</b> ở Trang chủ
    (kèm dòng ở thanh Thông báo), cột Thời Gian Đặt Hàng của mã đó có nhãn <span class="badge badge-warning">Chờ khai</span>. Bấm dòng trên thẻ sẽ mở
    màn hình danh mục với hộp thoại điều chỉnh mở sẵn. Lưu thì thẻ và nhãn biến mất.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>; duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt. Sửa và Khoá
        bắt buộc ghi lý do. <b>Đơn vị tính</b> khai ở tab Chất chuẩn của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
