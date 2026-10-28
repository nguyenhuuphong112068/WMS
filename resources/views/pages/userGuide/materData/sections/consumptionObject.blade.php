<p>Danh sách các <b>đối tượng tiêu thụ vật tư</b> (thường là thiết bị), dùng để theo dõi lượng vật tư mà mỗi đối tượng
    tiêu thụ. Mỗi dòng là một cặp <b>(đối tượng, tần suất)</b>: cùng một mã thiết bị có lịch hằng tháng và hằng năm thì là
    hai dòng riêng.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/doi-tuong.png', 'caption' => 'Màn hình Đối Tượng'])

<h3>Lấy dữ liệu từ phần mềm CAL</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-sync-alt"></i> Đồng bộ từ phần mềm CAL</span>
        và xác nhận.</li>
    <li>Hệ thống đọc danh sách thiết bị và lịch bảo trì từ CAL của khối <b>B1</b> và <b>B2</b>, gồm <i>Thiết bị sản xuất</i>,
        <i>Thiết bị tiện ích</i>, <i>Thiết bị kiểm nghiệm</i>. Thiết bị mới được thêm; tên và vị trí của thiết bị đã có được
        cập nhật theo CAL.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Với dòng lấy từ CAL, <b>Loại</b>, <b>Mã</b> và <b>Tần suất</b> là khoá nhận diện nên không sửa được. Tên và vị trí
        sửa trên WMS sẽ bị ghi đè ở lần đồng bộ sau. Muốn sửa lâu dài, hãy sửa bên CAL.</div>
</div>

<h3>Thêm tay một đối tượng</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/doi-tuong-form.png', 'caption' => 'Hộp thoại thêm mới - Đối Tượng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Loại Đối Tượng', true, 'Thiết bị sản xuất / tiện ích / kiểm nghiệm.'],
    ['Mã Đối Tượng', true, 'Cùng loại, cùng mã và cùng tần suất thì không được trùng.'],
    ['Tần Suất', true, 'Từ <i>Hằng ngày</i>, <i>Hằng tuần</i>, <i>Hằng tháng</i>, <i>Hằng quý</i> tới <i>7 năm/lần</i>.'],
    ['Tên Đối Tượng', true, 'Tên thiết bị hoặc đối tượng.'],
    ['Vị Trí', false, 'Nơi đặt thiết bị.'],
]])
