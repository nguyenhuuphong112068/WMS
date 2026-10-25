<p>Khai báo các phòng ban của từng công ty. Phòng ban là đơn vị sở hữu kho, danh mục hàng của phòng, tồn kho
    và phiếu nghiệp vụ. Người dùng chọn phòng ban đang làm việc ở thanh trên cùng.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Công Ty', true, 'Công ty mà phòng ban trực thuộc. Phải khai <a href="#cong-ty">Công Ty</a> trước.'],
    ['Tên Viết Tắt', true, 'Ví dụ: <span class="ug-key">QC1</span>, <span class="ug-key">AD</span>. Không trùng <b>trong cùng một công ty</b>.'],
    ['Tên Phòng Ban', true, 'Tên đầy đủ. Không trùng trong cùng một công ty.'],
    ['Bộ Phận Mua Hàng', false, 'Chọn <b>Cung Ứng</b> hoặc <b>Hành Chánh</b> nếu phòng ban này là bộ phận mua hàng. Để trống với phòng ban thông thường.'],
    ['Phòng ban chung', false, 'Tick khi phòng ban chỉ dùng để gắn tài khoản người dùng: không hiện ở Chuyển Bộ Phận và không tham gia nghiệp vụ kho.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Khoá một phòng ban thì phòng đó không còn được chọn ở các màn hình. Kiểm tra kỹ trước khi khoá phòng đang có tồn kho.</div>
</div>
