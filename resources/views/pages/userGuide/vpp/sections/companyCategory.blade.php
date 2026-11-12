<p>Mô tả <b>bản chất</b> của VPP, dùng chung cho toàn công ty. Mỗi dòng là một tổ hợp <b>Tên VPP + Nhà sản
    xuất</b>. Các phòng chỉ khai và dùng được VPP đã có ở đây và đã <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › VPP › tab Danh mục VPP công ty'])

<h3>Thêm nhiều mã một lần</h3>
<p>Hộp thoại <b>Thêm Danh Mục VPP Công Ty Mới</b> là một bảng, <b>mỗi dòng là một mã VPP</b>. Bấm <b>Lưu</b> một lần là
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
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/danh-muc-cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục VPP Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã', false, 'Hệ thống tự sinh khi lưu, dạng <span class="ug-key">P00001</span> (mã chuyển từ Vật Tư giữ phần số, đổi chữ M thành P).'],
    ['Tên VPP', true, 'Chọn từ <b>Dữ Liệu Gốc › Tên Vật Tư</b> (đã duyệt) - danh sách tên dùng chung với Vật Tư.'],
    ['Nhà Sản Xuất', true, 'Chọn từ <b>Dữ Liệu Gốc › Nhà Sản Xuất</b>.'],
    ['Thông Tin Kỹ Thuật', false, 'Quy cách, kích thước, tiêu chuẩn. Tối đa 100 ký tự.'],
    ['Part Number', false, 'Mã P/N của hãng. Tối đa 100 ký tự.'],
    ['Phân loại (4 cột)', true, 'Mỗi tiêu chí một cột, chọn đủ cả 4: <b>Giá Trị</b> (Thấp/Cao), <b>Mức Độ Quan Trọng</b> (Thấp/Cao), <b>Hàng Hoá Nguy Hiểm</b> (Có/Không), <b>Cần Hiệu Chuẩn Trước Khi Dùng</b> (Có/Không). Hộp thoại Cập nhật vẫn chọn bằng ô tick như cũ.'],
]])

<h3>Phân loại ảnh hưởng tới đâu?</h3>
<ul>
    <li><b>Trình ký đề nghị cấp phát</b>: các tiêu chí là điều kiện để xác định vai trò nào phải ký
        (Dữ Liệu Gốc › Trình Ký Đề Nghị CP VPP).</li>
    <li><b>Cần hiệu chuẩn trước khi dùng = Có</b>: VPP phải qua bước <a href="#hieu-chuan">Hiệu chuẩn</a> mới cấp phát được.</li>
    <li><b>Bộ phận mua hàng</b>: không chọn trên form - mọi VPP do <b>Hành Chánh</b> mua và cấp phát, nên mọi mã đều đưa được vào <a href="#de-nghi-vpp">Đề nghị VPP đến HC</a>.</li>
</ul>

<h3>Thời gian đặt hàng</h3>
<p>Danh mục VPP <b>không cần Thời Gian Đặt Hàng</b>: VPP do Hành Chánh mua và cấp phát từ kho VPP, cột
    <b>Thời Gian Đặt Hàng</b> luôn hiện <i>Không cần</i>, không có nút sửa và không phát sinh thẻ "Mã mới chờ khai" ở Trang chủ.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>. Duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt.
        Sửa và Khoá bắt buộc ghi lý do điều chỉnh. <b>Đơn vị tính</b> không khai ở đây mà khai ở tab VPP của phòng. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
