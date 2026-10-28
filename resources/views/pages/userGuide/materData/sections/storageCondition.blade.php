<p>Các điều kiện bảo quản hàng hoá, ví dụ <i>Nhiệt độ phòng (15 - 30 °C)</i>, <i>Tủ lạnh 2 - 8 °C</i>,
    <i>Tránh ánh sáng</i>. Điều kiện được gắn vào danh mục hàng để người dùng biết cách bảo quản.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/dieu-kien-bao-quan.png', 'caption' => 'Màn hình Điều Kiện Bảo Quản'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/dieu-kien-bao-quan-form.png', 'caption' => 'Hộp thoại thêm mới - Điều Kiện Bảo Quản'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Điều Kiện Bảo Quản', true, 'Không trùng. Nên ghi rõ khoảng nhiệt độ hoặc độ ẩm nếu có.'],
    ['Ghi Chú', false, 'Thông tin thêm, tối đa 500 ký tự.'],
]])
