<p>Mỗi phòng ban tự đặt <b>bộ nhóm phân loại riêng</b> cho vật tư của phòng mình, ví dụ <i>Vật tư HPLC</i>,
    <i>Dụng cụ vi sinh</i>. Nhóm này được chọn khi khai <b>Vật Tư Của Phòng</b> và có thể làm điều kiện ở
    <a href="#trinh-ky-cap-phat">Trình Ký Đề Nghị CP Vật Tư</a>.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Phân Loại', true, 'Không trùng trong cùng phòng ban, tối đa 100 ký tự.'],
]])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>
        Màn hình luôn làm việc trên <b>phòng ban đang chọn</b> ở thanh trên cùng, không chọn phòng tay trong form.
        Phân loại này khác với các tiêu chí ở Danh Mục Vật Tư Công Ty (giá trị, mức độ quan trọng, hàng nguy hiểm, cần
        hiệu chuẩn), là các tiêu chí dùng chung toàn công ty.
    </div>
</div>
