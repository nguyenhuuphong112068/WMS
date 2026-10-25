<p>Khai báo trước <b>ai phải ký</b> đề nghị cấp phát vật tư của phòng. Mỗi dòng là một <b>vai trò</b> nhận trình
    ký. Khi người dùng lập đề nghị cấp phát vật tư, hệ thống dựa vào bảng này để tự xếp các bước ký.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Vai Trò Nhận Trình Ký', true, 'Mỗi vai trò chỉ khai <b>một lần</b> trong phòng (kể cả dòng đã khoá, muốn dùng lại thì mở khoá).'],
    ['Cấp Ký', true, 'Số thứ tự từ 1 đến 50. Phiếu được ký lần lượt <b>từ cấp nhỏ tới cấp lớn</b>; 1 là cấp ký đầu tiên.'],
    ['Người Ký', true, 'Bấm <b>Thêm người ký</b> để chọn một hoặc nhiều người thuộc vai trò. Chỉ cần <b>một người</b> trong số đó ký là xong bước.'],
    ['Điều Kiện Nhận Trình Ký', false, 'Bấm <b>Thêm nhóm điều kiện</b> (tối đa 10 nhóm). Để trống thì vai trò này nhận trình ký <b>mọi đề nghị</b>.'],
]])

<h3>Cách hiểu điều kiện</h3>
<ul>
    <li>Mỗi nhóm điều kiện gồm một hoặc nhiều tiêu chí: <b>Theo Giá Trị</b>, <b>Theo Mức Độ Quan Trọng</b>,
        <b>Theo Hàng Hoá Nguy Hiểm</b>, <b>Theo Cần Hiệu Chuẩn Trước Khi Dùng</b> và <b>Phân Loại Của Phòng</b>
        (nếu phòng đã khai <a href="#phan-loai">Phân Loại</a>).</li>
    <li>Các tiêu chí <b>trong cùng một nhóm</b> phải thoả <b>tất cả</b> (VÀ).</li>
    <li>Giữa <b>các nhóm</b> chỉ cần thoả <b>một nhóm</b> (HOẶC).</li>
    <li>Đề nghị có ít nhất một vật tư thoả điều kiện thì vai trò đó nhận trình ký.</li>
</ul>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>Ví dụ: vai trò <i>Trưởng phòng</i> cấp 2, điều kiện <b>(Giá cao)</b> HOẶC <b>(Hàng nguy hiểm VÀ Rất quan trọng)</b>.
        Đề nghị chỉ có vật tư giá thấp, không nguy hiểm thì Trưởng phòng không cần ký.</div>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Màn hình làm việc trên <b>phòng ban đang chọn</b> ở thanh trên cùng. Các bước ký được chép vào phiếu đúng lúc
        bấm <b>Trình ký</b>: phiếu đã trình ký giữ nguyên quy trình lúc đó, còn phiếu chưa trình ký sẽ theo quy trình mới
        nhất. Không vai trò nào thoả điều kiện thì đề nghị được duyệt thẳng.</div>
</div>
