<p>Tổ là đơn vị nhỏ bên trong một phòng ban, ví dụ <i>Tổ Hoá Lý 1</i>, <i>Tổ Vi Sinh</i>.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng Ban', true, 'Phòng ban mà tổ trực thuộc.'],
    ['Tên Tổ', true, 'Không trùng tên với tổ khác <b>trong cùng phòng ban</b>. Hai phòng ban khác nhau được đặt trùng tên tổ.'],
]])
