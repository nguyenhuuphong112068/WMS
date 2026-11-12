<p>Khai báo các bước ký cho <b>đề nghị chuyển vật tư giữa các phòng ban</b>. Mỗi dòng là <b>một bước ký</b>, khai riêng
    cho từng công ty. Đề nghị VPP (gửi Hành Chánh / liên phòng ban) khai ở
    <a href="#trinh-ky-lien-phong-ban-vpp">Trình Ký Đề Nghị Liên Phòng Ban VPP</a>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/trinh-ky-lien-phong-ban.png', 'caption' => 'Màn hình Trình Ký Đề Nghị Liên Phòng Ban'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/trinh-ky-lien-phong-ban-form.png', 'caption' => 'Hộp thoại thêm mới - Trình Ký Đề Nghị Liên Phòng Ban'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Loại Đề Nghị', true, 'Vật Tư chỉ có một loại: <b>Đề nghị liên phòng ban</b>.'],
    ['Công Ty Của Phòng Đề Nghị', true, 'Quy trình áp dụng cho các phòng đề nghị thuộc công ty này.'],
    ['Thứ Tự Bước', true, 'Từ 1 đến 10. Mỗi loại đề nghị trong một công ty không được có hai dòng cùng thứ tự bước.'],
    ['Vai Trò Ký', true, 'Vai trò của người ký bước này.'],
    ['Phạm Vi Phòng Ban', true, '<b>Phòng đề nghị</b>: người có vai trò trên ở phòng lập đề nghị ký. <b>Phòng được đề nghị</b>: người ở phòng nhận đề nghị ký. <b>Phòng cố định</b>: luôn là một phòng ban cụ thể.'],
    ['Phòng Cố Định', false, 'Bắt buộc khi Phạm Vi là <b>Phòng cố định</b>.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Không khai bước nào cho một loại đề nghị thì đề nghị đó được <b>gửi thẳng</b> tới phòng nhận, không qua ký duyệt.
        Có khai bước thì phải ký đủ các bước, phiếu mới tới phòng nhận.</div>
</div>
