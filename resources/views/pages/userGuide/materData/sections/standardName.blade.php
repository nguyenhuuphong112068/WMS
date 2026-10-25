<p>Danh sách tên chất chuẩn (chuẩn đối chiếu, chuẩn làm việc...) dùng trong kiểm nghiệm. Danh mục chất chuẩn,
    phiếu nhập và đề nghị cấp phát chất chuẩn đều chọn từ đây.</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Chuẩn', true, 'Không trùng.'],
    ['Tên Hoạt Chất', false, 'Hoạt chất chính của chất chuẩn.'],
    ['Số CAS', false, 'Tối đa 100 ký tự.'],
]])
