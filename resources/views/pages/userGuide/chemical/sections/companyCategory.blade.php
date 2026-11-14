<p>Mô tả <b>bản chất</b> của hoá chất, dùng chung toàn công ty. Mỗi dòng gắn <b>một tên hoá chất</b> với <b>một nhà sản xuất</b>.
    Các phòng chỉ khai và dùng được hoá chất đã có ở đây và <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › Hoá Chất › tab Danh mục hoá chất công ty'])

<h3>Thêm nhiều mã một lần</h3>
<p>Hộp thoại <b>Thêm Danh Mục Hoá Chất Công Ty Mới</b> là một bảng, <b>mỗi dòng là một mã hoá chất</b>. Bấm <b>Lưu</b> một lần là thêm được
    tất cả các dòng.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới</span>, hộp thoại mở sẵn một dòng trống.</li>
    <li>Nếu nhiều mã giống nhau ở vài ô (Loại, Nhà sản xuất, Điều kiện bảo quản, Cảnh báo an toàn): chọn trước ở hàng <b>Điền nhanh cho mọi dòng</b> ngay dưới tiêu đề bảng.
        Giá trị ở đây áp ngay cho mọi dòng đang có và điền sẵn cho dòng thêm sau.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm dòng</span> hoặc <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-flask"></i> Chọn từ dữ liệu gốc</span> (bảng chọn vẫn mở, mỗi lần bấm <i class="fas fa-check"></i> là thêm một tên vào dòng còn trống / dòng mới, nút đổi thành <i class="fas fa-check-double"></i>), hoặc
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clone"></i></span> ở một dòng để nhân bản dòng đó
        (giữ loại, nhà sản xuất, tỉ trọng, bảo quản, cảnh báo - chỉ cần chọn lại tên). Nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> bỏ dòng.</li>
    <li>Bấm <b>Lưu</b>. Mã cấp liên tiếp theo thứ tự dòng (cột <b>Mã</b> là mã dự kiến). Dòng để trống hoàn toàn được bỏ qua.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: hộp thoại mở lại với đủ các dòng vừa nhập, lỗi ghi theo
        <b>"Dòng N: ..."</b> ở đầu hộp thoại và dòng lỗi tô nền đỏ nhạt. Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Hoá Chất Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã', false, 'Hệ thống tự sinh khi lưu, cấp liên tiếp theo thứ tự dòng.'],
    ['Loại', false, 'Gõ hoặc chọn gợi ý: ' . implode(', ', config('chemical.types')) . '.'],
    ['Tên Hoá Chất', true, 'Chọn từ <b>Dữ Liệu Gốc › Hoá Chất</b>. Một danh mục chỉ mang một tên hoá chất. Không trùng tổ hợp <b>Tên - Loại - Nhà sản xuất</b> với danh mục đang có và giữa các dòng.'],
    ['Nhà Sản Xuất', true, 'Chọn từ <b>Dữ Liệu Gốc › Nhà Sản Xuất</b>.'],
    ['Tỉ Trọng d (g/ml)', false, 'Dùng để quy đổi giữa kg và lít (ví dụ khi đối chiếu ngưỡng PL IV).'],
    ['Điều Kiện Bảo Quản', false, 'Chọn từ Dữ Liệu Gốc.'],
    ['Phân Loại NĐ 24/2026', false, 'Hiện ngay dưới tên sau khi chọn, hệ thống tự suy từ hoạt chất thành phần. Muốn đổi thì sửa ở Dữ Liệu Gốc.'],
    ['Cảnh Báo An Toàn', false, 'Ô chọn nhiều nhóm GHS (lỏng dễ cháy, ăn mòn, oxy hoá, độc...), mỗi nhóm kèm hình đồ. In ra dải giữa của nhãn dán lô và dùng để xét tương kỵ. Nút <b>Sơ đồ tương kỵ</b> trên thanh công cụ mở sơ đồ lưu trữ GHS.'],
    ['Hàm Lượng Hoạt Chất (%)', false, 'Chỉ có ở form <b>Sửa</b>. Dùng khi đối chiếu ngưỡng PL IV: khối lượng hoạt chất = khối lượng hoá chất × %.'],
]])

<h3>Các cột cần chú ý trên bảng</h3>
<ul>
    <li><b>Phân Loại</b>: các nhóm NĐ 24/2026 suy được. Mã thuộc nhóm 9 (Bảng A) hoặc nhóm 10 (Bảng B) có thêm số liệu đối chiếu
        ngưỡng tồn trữ toàn công ty; bấm vào con số để xem các lô tạo nên nó.</li>
    <li><b>Phòng Ban Đang Dùng</b>: các phòng đã khai hoá chất này ở tab Hoá chất của phòng.</li>
    <li><b>Khai Báo Cổng TT Quốc Gia</b>: khai báo được <b>nhiều lần</b> (mỗi nhà sản xuất / catalogue một lần). Bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-check mr-1"></i>Khai báo</span> (lần đầu) hoặc
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-plus mr-1"></i>Khai báo thêm</span>, nhập 2 ô bắt buộc
        <b>Mã hồ sơ khai báo</b> và <b>Code nhà sản xuất</b> (mã hàng / catalogue number) rồi nhập lại mật khẩu. Cột liệt kê từng lần
        khai báo: Code NSX, Hồ sơ, người · ngày; mỗi lần được ghi vào lịch sử và không làm danh mục phải duyệt lại.
        Khai sai thì bấm <i class="fas fa-pen"></i> ở góc phải khung của lần khai báo đó: hộp <b>Sửa khai báo Cổng thông tin quốc gia</b>
        điền sẵn Mã hồ sơ + Code nhà sản xuất cũ, sửa rồi nhập <b>Lý do điều chỉnh</b> (bắt buộc, không cần mật khẩu). Thay đổi ghi vào
        lịch sử danh mục (<i>Sửa khai báo Cổng TTQG</i>) và Audit Trail, khung hiện thêm <i>· đã sửa</i>. Không đổi gì mà vẫn lưu thì hệ
        thống báo <i>"Thông tin khai báo không có thay đổi."</i> Khai báo và sửa đều cần quyền <b>Sửa Danh Mục Hoá Chất</b>. Code NSX khai ở đây hiện ở hộp <a href="#du-tru">Thêm mặt hàng dự trù</a>.</li>
</ul>

<h3>Điều chỉnh thời gian đặt hàng</h3>
<p>Hộp thoại <b>Thêm mới</b> và <b>Cập nhật</b> không có ô Thời Gian Đặt Hàng - số ngày do người có quyền khai sau. Người có quyền <span class="ug-key">category_chemical_lead_time</span>
    (<b>Điều Chỉnh Thời Gian Đặt Hàng Hoá Chất</b>) thấy nút <span class="ug-key"><i class="fas fa-pen"></i></span> ngay trong cột
    <b>Thời Gian Đặt Hàng</b> của bảng - không cần quyền Sửa danh mục.</p>
<ol>
    <li>Bấm nút <span class="ug-key"><i class="fas fa-pen"></i></span> ở dòng cần đổi.</li>
    <li>Nhập số ngày mới (số nguyên, để trống nếu chưa xác định) và <b>Lý Do Điều Chỉnh</b> (bắt buộc).</li>
    <li>Bấm <b>Lưu thay đổi</b>. Bản ghi <b>giữ nguyên trạng thái duyệt</b>, thay đổi được ghi vào Lịch sử thay đổi (số nhỏ ở góc nút Sửa, mở ra bảng <b>Lịch Sử Thay Đổi</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng danh sách; ô đã đổi so với lần trước được tô vàng, cột chữ nhạt là giá trị hiện tại của mục không lưu trong lịch sử) và Audit Trail.</li>
</ol>
<p><b>Mã mới chờ khai:</b> mỗi khi thêm mã mới, người có quyền trên nhận thẻ <b>Mã mới chờ khai thời gian đặt hàng</b> ở Trang chủ
    (kèm dòng ở thanh Thông báo), cột Thời Gian Đặt Hàng của mã đó có nhãn <span class="badge badge-warning">Chờ khai</span>. Bấm dòng trên thẻ sẽ mở
    màn hình danh mục với hộp thoại điều chỉnh mở sẵn. Lưu thì thẻ và nhãn biến mất.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>; duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt. Sửa và
        Khoá bắt buộc ghi lý do. <b>Đơn vị tính</b> không khai ở đây mà ở tab Hoá chất của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
