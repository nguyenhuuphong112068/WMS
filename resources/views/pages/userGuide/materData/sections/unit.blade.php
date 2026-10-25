<p>Khai báo các đơn vị tính (g, kg, ml, L, chai, hộp...) cùng hệ số quy đổi về <b>đơn vị gốc</b> của nhóm.
    Nhờ hệ số này, hệ thống cộng tồn và đối chiếu ngưỡng đúng dù mỗi lần nhập dùng một đơn vị khác nhau.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Ký Hiệu', true, 'Ký hiệu ngắn, ví dụ <span class="ug-key">kg</span>, <span class="ug-key">mL</span>. Không trùng.'],
    ['Tên Đơn Vị Tính', true, 'Tên đầy đủ, ví dụ <i>Kilogram</i>. Không trùng.'],
    ['Nhóm Đơn Vị', true, '<b>Khối lượng</b> (gốc là g), <b>Thể tích</b> (gốc là ml) hoặc <b>Đếm / Bao bì</b> (cái, chai, thùng...).'],
    ['Hệ Số Quy Đổi', true, 'Số đơn vị gốc trong <b>1</b> đơn vị đang khai, phải lớn hơn 0. Màn hình hiện sẵn gợi ý theo nhóm đã chọn.'],
]])

<h3>Ví dụ hệ số</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th>Nhóm</th>
                <th>Đơn vị gốc</th>
                <th>Ví dụ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Khối lượng</td>
                <td>g</td>
                <td>mg = 0,001 · g = 1 · kg = 1000 · tấn = 1000000</td>
            </tr>
            <tr>
                <td>Thể tích</td>
                <td>ml</td>
                <td>µl = 0,001 · ml = 1 · L = 1000 · m3 = 1000000</td>
            </tr>
            <tr>
                <td>Đếm / Bao bì</td>
                <td>đơn vị</td>
                <td>cái, chai, lọ, hộp, thùng, bao... đều để hệ số 1</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Nhóm <b>Đếm / Bao bì</b> không tự quy đổi sang kg hay lít được. Muốn biết 1 chai bằng bao nhiêu ml, phải khai
        quy cách đóng gói cho từng mặt hàng ở Danh Mục. Sửa hệ số của đơn vị đang dùng sẽ ảnh hưởng tới mọi phép quy đổi
        sau đó, cần kiểm tra kỹ trước khi duyệt.</div>
</div>
