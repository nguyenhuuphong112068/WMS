<p>Danh sách tên trạng thái dùng chung của hệ thống.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/trang-thai.png', 'caption' => 'Màn hình Trạng Thái'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/trang-thai-form.png', 'caption' => 'Hộp thoại thêm mới - Trạng Thái'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Trạng Thái', true, 'Không trùng.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Đây là dữ liệu hệ thống. Chỉ thêm hoặc sửa khi có hướng dẫn của quản trị viên.</div>
</div>
