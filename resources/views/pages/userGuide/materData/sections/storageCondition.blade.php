<p>Các điều kiện bảo quản hàng hoá, ví dụ <i>Nhiệt độ phòng (15 - 30 °C)</i>, <i>Tủ lạnh 2 - 8 °C</i>,
    <i>Tránh ánh sáng</i>. Điều kiện được gắn vào danh mục hàng để người dùng biết cách bảo quản.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Điều Kiện Bảo Quản', true, 'Không trùng. Nên ghi rõ khoảng nhiệt độ hoặc độ ẩm nếu có.'],
    ['Ghi Chú', false, 'Thông tin thêm, tối đa 500 ký tự.'],
]])
