<p>Danh sách tên vật tư dùng chung toàn công ty, ví dụ <i>Găng tay nitrile</i>, <i>Đầu côn 1000 µl</i>. Danh mục vật
    tư công ty và vật tư của phòng đều chọn tên từ đây.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-vat-tu.png', 'caption' => 'Màn hình Tên Vật Tư'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-vat-tu-form.png', 'caption' => 'Hộp thoại thêm mới - Tên Vật Tư'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Vật Tư', true, 'Không trùng. Không ghi quy cách hay hãng vào tên, vì các thông tin đó khai ở Danh Mục Vật Tư.'],
]])
