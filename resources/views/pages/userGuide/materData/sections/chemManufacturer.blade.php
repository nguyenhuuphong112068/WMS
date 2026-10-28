<p>Danh sách hãng sản xuất của hàng hoá, ví dụ <i>Merck</i>, <i>Sigma-Aldrich</i>. Nhà sản xuất được chọn khi
    khai danh mục và khi nhập hàng.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/nha-san-xuat.png', 'caption' => 'Màn hình Nhà Sản Xuất'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/nha-san-xuat-form.png', 'caption' => 'Hộp thoại thêm mới - Nhà Sản Xuất'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Viết Tắt', true, 'Tên ngắn hiện trên bảng và ô chọn. Không trùng.'],
    ['Tên Nhà Sản Xuất', true, 'Tên đầy đủ. Không trùng.'],
]])
