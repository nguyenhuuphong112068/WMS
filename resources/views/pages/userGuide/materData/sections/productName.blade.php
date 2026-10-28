<p>Danh sách tên sản phẩm của nhà máy. Khi đề nghị và cấp phát <b>chất chuẩn</b>, người dùng chọn chất chuẩn
    được dùng để kiểm nghiệm sản phẩm nào.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-san-pham.png', 'caption' => 'Màn hình Tên Sản Phẩm'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-san-pham-form.png', 'caption' => 'Hộp thoại thêm mới - Tên Sản Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm', true, 'Không trùng.'],
]])
