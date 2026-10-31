<p>Tổ là đơn vị nhỏ bên trong một phòng ban, ví dụ <i>Tổ Hoá Lý 1</i>, <i>Tổ Vi Sinh</i>.</p>
<p>Đang làm việc ở một phòng ban nghiệp vụ thì màn hình chỉ hiện tổ của <b>chính phòng đó</b>, và chỉ thêm / sửa / khoá được tổ của phòng đó. Phòng ban chung (Ban Giám Đốc...) xem được tổ của mọi phòng trong công ty.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/to.png', 'caption' => 'Màn hình Tổ'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/to-form.png', 'caption' => 'Hộp thoại thêm mới - Tổ'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng Ban', true, 'Phòng ban mà tổ trực thuộc.'],
    ['Tên Tổ', true, 'Không trùng tên với tổ khác <b>trong cùng phòng ban</b>. Hai phòng ban khác nhau được đặt trùng tên tổ.'],
]])
