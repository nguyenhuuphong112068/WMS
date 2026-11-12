<p>Mô tả <b>bản chất</b> của vật tư, dùng chung cho toàn công ty. Mỗi dòng là một tổ hợp <b>Tên vật tư + Nhà sản
    xuất</b>. Các phòng chỉ khai và dùng được vật tư đã có ở đây và đã <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › Vật Tư › tab Danh mục vật tư công ty'])

<h3>Thêm nhiều mã một lần</h3>
<p>Hộp thoại <b>Thêm Danh Mục Vật Tư Công Ty Mới</b> là một bảng, <b>mỗi dòng là một mã vật tư</b>. Bấm <b>Lưu</b> một lần là
    thêm được tất cả các dòng.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới</span>, hộp thoại mở sẵn một dòng trống.</li>
    <li>Nếu nhiều mã cùng nhà sản xuất / cùng phân loại: chọn trước ở hàng <b>Điền nhanh cho mọi dòng</b> (ngay dưới tiêu đề
        bảng). Giá trị chọn ở đây áp ngay cho mọi dòng đang có và điền sẵn cho dòng thêm sau.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm dòng</span> để thêm mã, hoặc
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clone"></i></span> ở một dòng để nhân bản dòng đó
        (giữ nhà sản xuất, thông tin, phân loại - chỉ cần chọn lại tên). Nút
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-trash-alt"></i></span> bỏ dòng.</li>
    <li>Bấm <b>Lưu</b>. Mã cấp liên tiếp theo thứ tự dòng (cột <b>Mã</b> là mã dự kiến). Dòng để trống hoàn toàn được bỏ qua.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Chỉ cần <b>một dòng lỗi</b> là không dòng nào được lưu: hộp thoại mở lại với đủ các dòng vừa nhập, lỗi ghi theo
        <b>"Dòng N: ..."</b> ở đầu hộp thoại và dòng lỗi tô nền đỏ nhạt. Sửa xong bấm Lưu lại. Mỗi lần lưu tối đa 200 dòng.</div>
</div>

<h3>Các ô nhập (mỗi dòng)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Vật Tư Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã', false, 'Hệ thống tự sinh khi lưu, dạng <span class="ug-key">M00001</span>.'],
    ['Tên Vật Tư', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Vật Tư</b> (đã duyệt).'],
    ['Nhà Sản Xuất', true, 'Chọn từ <b>Dữ Liệu Gốc › Nhà Sản Xuất</b>.'],
    ['Thông Tin Kỹ Thuật', false, 'Quy cách, kích thước, tiêu chuẩn. Tối đa 100 ký tự.'],
    ['Part Number', false, 'Mã P/N của hãng. Tối đa 100 ký tự.'],
    ['Phân loại (4 cột)', true, 'Mỗi tiêu chí một cột, chọn đủ cả 4: <b>Giá Trị</b> (Thấp/Cao), <b>Mức Độ Quan Trọng</b> (Thấp/Cao), <b>Hàng Hoá Nguy Hiểm</b> (Có/Không), <b>Cần Hiệu Chuẩn Trước Khi Dùng</b> (Có/Không). Hộp thoại Cập nhật vẫn chọn bằng ô tick như cũ.'],
]])

<h3>Phân loại ảnh hưởng tới đâu?</h3>
<ul>
    <li><b>Trình ký đề nghị cấp phát</b>: các tiêu chí là điều kiện để xác định vai trò nào phải ký
        (Dữ Liệu Gốc › Trình Ký Đề Nghị CP Vật Tư).</li>
    <li><b>Cần hiệu chuẩn trước khi dùng = Có</b>: vật tư phải qua bước <a href="#hieu-chuan">Hiệu chuẩn</a> mới cấp phát được.</li>
    <li><b>Bộ phận mua hàng</b>: không chọn trên form - mọi vật tư do <b>Cung Ứng</b> mua. Văn phòng phẩm do Hành Chánh mua khai ở module
        <a href="{{ route('pages.userGuide.show', 'vpp') }}">VPP</a>.</li>
</ul>

<h3>Điều chỉnh thời gian đặt hàng</h3>
<p>Hộp thoại <b>Thêm mới</b> và <b>Cập nhật</b> không có ô Thời Gian Đặt Hàng - số ngày do người có quyền khai sau.
    Người có quyền <span class="ug-key">category_material_lead_time</span>
    (<b>Điều Chỉnh Thời Gian Đặt Hàng</b>) thấy nút <span class="ug-key"><i class="fas fa-pen"></i></span> ngay trong cột
    <b>Thời Gian Đặt Hàng</b> của bảng - không cần quyền Sửa danh mục.</p>
<ol>
    <li>Bấm nút <span class="ug-key"><i class="fas fa-pen"></i></span> ở dòng cần đổi.</li>
    <li>Nhập số ngày mới (số nguyên, để trống nếu chưa xác định) và <b>Lý Do Điều Chỉnh</b> (bắt buộc).</li>
    <li>Bấm <b>Lưu thay đổi</b>. Bản ghi <b>giữ nguyên trạng thái duyệt</b>, thay đổi được ghi vào Lịch sử thay đổi (số nhỏ ở góc nút Sửa, mở ra bảng <b>Lịch Sử Thay Đổi</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng danh sách; ô đã đổi so với lần trước được tô vàng, cột chữ nhạt là giá trị hiện tại của mục không lưu trong lịch sử) và Audit Trail.</li>
</ol>
<p><b>Mã mới chờ khai:</b> mỗi khi thêm mã mới, người có quyền trên nhận thẻ <b>Mã mới chờ khai thời gian đặt hàng</b> ở Trang chủ
    (kèm dòng ở thanh Thông báo), cột Thời Gian Đặt Hàng của mã đó có nhãn <span class="badge badge-warning">Chờ khai</span>. Bấm dòng trên thẻ sẽ mở
    màn hình danh mục với hộp thoại điều chỉnh mở sẵn; lưu thì thẻ và nhãn biến mất.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>. Duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt.
        Sửa và Khoá bắt buộc ghi lý do điều chỉnh. <b>Đơn vị tính</b> không khai ở đây mà khai ở tab Vật tư của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
