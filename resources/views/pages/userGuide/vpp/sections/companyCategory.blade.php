<p>Mô tả <b>bản chất</b> của VPP, dùng chung cho toàn công ty. Mỗi dòng là một tổ hợp <b>Tên VPP + Nhà sản
    xuất</b>. Các phòng chỉ khai và dùng được VPP đã có ở đây và đã <b>Đã duyệt</b>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/danh-muc-cong-ty.png', 'caption' => 'Màn hình Danh Mục › VPP › tab Danh mục VPP công ty'])

<h3>Thêm nhiều mã một lần</h3>
<p>Hộp thoại <b>Thêm Danh Mục VPP Công Ty Mới</b> là một bảng, <b>mỗi dòng là một mã VPP</b>. Bấm <b>Lưu</b> một lần là
    thêm được tất cả các dòng.</p>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm mới</span>, hộp thoại mở sẵn một dòng trống.</li>
    <li>Nếu nhiều mã cùng nhà sản xuất / đơn vị / nhóm dự trù: chọn trước ở hàng <b>Điền nhanh cho mọi dòng</b> (ngay dưới tiêu đề
        bảng). Giá trị chọn ở đây áp ngay cho mọi dòng đang có và điền sẵn cho dòng thêm sau.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus"></i> Thêm dòng</span> để thêm mã, hoặc
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-clone"></i></span> ở một dòng để nhân bản dòng đó
        (giữ nhà sản xuất, đơn vị, nhóm dự trù - chỉ cần chọn lại tên). Nút
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
    ['Đơn Vị Tính', true, '<b>Cả công ty dùng chung một đơn vị</b> cho mỗi mã VPP: mọi phòng nhập, xuất, ghi tồn, dự trù theo đơn vị này. Phòng không tự đổi đơn vị.'],
    ['Nhóm Dự Trù', true, 'Mỗi VPP thuộc đúng một nhóm theo <b>cách dự trù</b>:<ul class="mb-0">'
        . collect(\App\Support\VppClassification::CRITERIA['group']['options'])->map(fn ($name) => '<li>' . e($name) . '</li>')->implode('')
        . '</ul>'],
]])

<h3>Bộ lọc và cột trên bảng</h3>
<ul>
    <li>Cột <b>Đơn Vị</b> và <b>Nhóm Dự Trù</b> (chip ngắn, rê chuột để xem tên đầy đủ). Bộ lọc <b>Nhóm dự trù</b> trên thanh công
        cụ thu gọn bảng theo từng nhóm hoặc <i>Chưa phân loại</i>.</li>
    <li>Cột <b>Sử Dụng</b> cho biết mã đang dùng hay đã khoá; phòng nào cũng tự có dòng của mã này ở tab
        <a href="#vat-tu-cua-phong">VPP của phòng</a>.</li>
    <li>VPP <b>không có</b> Part Number, Thông Tin Kỹ Thuật và Thời Gian Đặt Hàng (khác Vật Tư) - không có hộp thoại điều chỉnh
        thời gian đặt hàng, Trang chủ không có thẻ "chờ khai thời gian đặt hàng" cho VPP.</li>
</ul>

<h3>Nhóm dự trù ảnh hưởng tới đâu?</h3>
<ul>
    <li><b>Trình ký Đề nghị VPP</b>: Nhóm Dự Trù (cùng Phân Loại Của Phòng) là tiêu chí điều kiện để xác định vai trò nào phải ký.</li>
    <li><b>Bộ phận mua hàng</b>: không chọn trên form - mọi VPP do <b>Hành Chánh</b> mua và cấp phát, nên mọi mã đều đưa được vào <a href="#de-nghi-vpp">Đề nghị VPP đến HC</a>.</li>
    <li>VPP không có tiêu chí "Cần hiệu chuẩn" nên không qua bước QA hiệu chuẩn như Vật Tư.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Bản ghi mới ở trạng thái <b>Chờ duyệt</b>. Duyệt phải nhập lại mật khẩu. Sửa bản ghi đã duyệt sẽ đưa về Chờ duyệt.
        Sửa và Khoá bắt buộc ghi lý do điều chỉnh. Bộ lọc <b>Trạng thái duyệt</b> trên bảng thu gọn danh sách theo Chờ duyệt / Đã duyệt / Từ chối / Đã khoá; bấm thẻ <b>Danh mục chờ duyệt</b> ở Trang chủ sẽ mở sẵn bộ lọc <b>Chờ duyệt</b>.</div>
</div>
