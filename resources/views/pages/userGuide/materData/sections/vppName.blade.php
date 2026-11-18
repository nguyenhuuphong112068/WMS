<p>Danh sách tên văn phòng phẩm dùng chung toàn công ty, ví dụ <i>Bút bi xanh</i>, <i>Giấy A4</i>. Danh Mục VPP công ty
    chỉ chọn tên ở đây. Tên VPP tách riêng khỏi <a href="#ten-vat-tu">Tên Vật Tư</a>: Tên Vật Tư không còn chứa tên VPP.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-vpp.png', 'caption' => 'Màn hình Tên VPP'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/ten-vpp-form.png', 'caption' => 'Hộp thoại thêm mới - Tên VPP'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên VPP', true, 'Không trùng, tối đa 255 ký tự. Không ghi quy cách hay đơn vị tính vào tên, vì các thông tin đó khai ở Danh Mục VPP.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Tên mới ở trạng thái <span class="ug-chip is-warn">Chờ duyệt</span>, chỉ chọn được ở Danh Mục VPP sau khi được duyệt.
        Sửa một tên đã duyệt sẽ đưa tên đó về chờ duyệt lại. Có thể nạp nhiều tên một lần qua
        <a href="#khoi-tao-du-lieu">Khởi Tạo Dữ Liệu</a> (sheet <b>37. Tên VPP</b>).</div>
</div>
