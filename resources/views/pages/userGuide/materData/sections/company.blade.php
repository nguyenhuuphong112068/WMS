<p>Phần mềm dùng chung cho nhiều công ty. Mỗi công ty có bộ phòng ban riêng. Phạm vi đối chiếu
    <b>Ngưỡng Tồn Trữ PL IV</b> (Nghị định 24/2026/NĐ-CP) tính trong từng công ty: hệ thống chỉ cộng tồn của các phòng
    ban thuộc cùng một công ty.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/cong-ty.png', 'caption' => 'Màn hình Công Ty'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/cong-ty-form.png', 'caption' => 'Hộp thoại thêm mới - Công Ty'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Viết Tắt', true, 'Tên ngắn, hiện trên menu chọn phòng ban. Không trùng với công ty khác. Tối đa 50 ký tự.'],
    ['Tên Công Ty', true, 'Tên đầy đủ theo giấy phép kinh doanh. Không trùng.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div><b>Mã công ty</b> do hệ thống tự sinh theo dạng <span class="ug-key">CT00001</span>, không nhập tay.</div>
</div>
