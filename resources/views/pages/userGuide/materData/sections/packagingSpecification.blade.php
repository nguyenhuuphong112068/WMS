<p>Danh sách các quy cách đóng gói dùng chung, ví dụ <i>Chai thuỷ tinh 500 ml</i>, <i>Can nhựa 25 L</i>,
    <i>Hộp 100 cái</i>. Quy cách được chọn khi khai danh mục và khi nhập hàng.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/quy-cach-dong-goi.png', 'caption' => 'Màn hình Quy Cách Đóng Gói'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/quy-cach-dong-goi-form.png', 'caption' => 'Hộp thoại thêm mới - Quy Cách Đóng Gói'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Quy Cách Đóng Gói', true, 'Mô tả ngắn gọn, đủ để phân biệt. Không trùng.'],
]])
