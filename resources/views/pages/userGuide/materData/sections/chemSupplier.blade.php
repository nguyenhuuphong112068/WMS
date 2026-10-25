<p>Danh sách đơn vị cung cấp hàng cho công ty, có thể khác nhà sản xuất (ví dụ đại lý phân phối). Nhà cung cấp
    được chọn trên phiếu nhập.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Nhà Cung Cấp', true, 'Không trùng.'],
    ['Địa Chỉ', false, 'Tối đa 500 ký tự.'],
    ['Mã Số Thuế', false, 'Tối đa 100 ký tự.'],
    ['Ghi Chú', false, 'Đầu mối liên hệ, điều kiện giao hàng... tối đa 1000 ký tự.'],
]])
