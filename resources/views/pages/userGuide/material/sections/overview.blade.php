<p>
    Module <b>Vật Tư</b> quản lý hàng tiêu hao của từng phòng ban (găng tay, đầu côn, phụ tùng, văn phòng phẩm...), từ lúc
    khai danh mục cho tới khi vật tư được cấp phát đem dùng. Các màn hình nằm rải ở 6 nhóm menu, mỗi nhóm có mục con
    <b>Vật Tư</b>.
</p>

<h3>Vòng đời một vật tư</h3>
<div class="ug-flow">
    <div class="ug-flow-step">
        <span class="ug-flow-no">1</span>
        <b>Khai danh mục</b>
        <a href="#danh-muc-cong-ty">Danh mục công ty</a> → <a href="#vat-tu-cua-phong">Vật tư của phòng</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">2</span>
        <b>Nhập kho</b>
        <a href="#nhap-vat-tu">Tạo phiếu nhập</a> → <a href="#cho-kiem-tra">Kiểm tra Đạt</a> thì mới vào tồn
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">3</span>
        <b>Đề nghị & ký</b>
        <a href="#de-nghi-noi-bo">Lập đề nghị</a> → trình ký → ký đủ các bước
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">4</span>
        <b>Cấp phát</b>
        <a href="#cho-cap-phat">Kho cấp phát</a>, trừ tồn ngay, ghi vào <a href="#so-cap-phat">Sổ cấp phát</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">5</span>
        <b>Theo dõi tồn</b>
        <a href="#ton-kho">Tồn kho</a>, <a href="#kha-dung-thang-toi">Khả dụng tháng tới</a>,
        <a href="#kiem-ke">Kiểm kê</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">6</span>
        <b>Bổ sung hàng</b>
        <a href="#can-du-tru">Vật tư cần dự trù</a> → <a href="#du-tru">Phiếu dự trù</a> → Cung Ứng mua → quay lại bước 2
    </div>
</div>

<h3>Các nhánh phụ</h3>
<ul>
    <li><a href="#lien-phong-ban">Đề nghị liên phòng ban</a>: xin vật tư từ kho của phòng khác.</li>
    <li><a href="#de-nghi-vpp">Đề nghị VPP</a>: xin văn phòng phẩm từ kho của phòng Hành Chánh.</li>
    <li><a href="#hieu-chuan">Hiệu chuẩn</a>: vật tư "Cần hiệu chuẩn trước khi dùng" phải được QA xác nhận đạt mới cấp phát được.</li>
    <li><a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>: xử lý vật tư hỏng, hết hạn.</li>
    <li><a href="#danh-sach-chu-ky">Danh sách theo chu kỳ</a>: hệ thống tự tạo đề nghị hoặc phiếu dự trù định kỳ.</li>
</ul>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Hầu hết màn hình vật tư làm việc trên <b>phòng ban đang chọn</b> ở thanh trên cùng. Dữ liệu nền (tên vật tư, đơn
        vị tính, nhà sản xuất, định khu, phân loại của phòng, trình ký) khai ở menu Dữ Liệu Gốc. Xem
        <a href="{{ route('pages.userGuide.show', 'materData') }}">Hướng dẫn Dữ Liệu Gốc</a>.</div>
</div>
