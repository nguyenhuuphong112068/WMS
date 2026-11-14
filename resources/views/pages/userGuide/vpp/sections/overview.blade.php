<p>
    Module <b>VPP</b> (văn phòng phẩm) quản lý các mặt hàng do <b>Hành Chánh</b> mua và cấp phát (giấy, bút, mực in...),
    từ lúc khai danh mục cho tới khi VPP được cấp phát đem dùng. Module tách từ Vật Tư nhưng dữ liệu, quyền riêng và đơn giản hơn: VPP dùng trong phòng được <b>ghi sổ trực tiếp</b>,
    không qua đề nghị nội bộ, trình ký và cấp phát. Các màn hình nằm ở 5 nhóm menu, mỗi nhóm có mục con <b>VPP</b>.
</p>

<h3>Vòng đời một VPP</h3>
<div class="ug-flow">
    <div class="ug-flow-step">
        <span class="ug-flow-no">1</span>
        <b>Khai danh mục</b>
        <a href="#danh-muc-cong-ty">Danh mục công ty</a> → <a href="#vat-tu-cua-phong">VPP của phòng</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">2</span>
        <b>Nhập kho</b>
        <a href="#nhap-vat-tu">Tạo phiếu nhập</a> → <a href="#cho-kiem-tra">Kiểm tra Đạt</a> thì mới vào tồn
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">3</span>
        <b>Lấy hàng</b>
        Kho phòng thiếu thì <a href="#de-nghi-vpp">đề nghị VPP đến HC</a> hoặc <a href="#lien-phong-ban">phòng khác</a> → ký 2 bước → nhận hàng
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">4</span>
        <b>Sử dụng</b>
        <a href="#su-dung-vpp">Sử dụng VPP</a>: quét / chọn lô, Lưu là trừ tồn ngay và ghi vào <a href="#so-cap-phat">Sổ sử dụng VPP</a>
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
        <a href="#can-du-tru">VPP cần dự trù</a> → <a href="#du-tru">Phiếu dự trù</a> → Hành Chánh mua → quay lại bước 2
    </div>
</div>

<div class="ug-callout">
    <i class="fas fa-project-diagram"></i>
    <div>Muốn xem phiếu đi qua những trang nào, ở trạng thái nào và tồn kho tăng giảm lúc nào, mở
        <a href="#so-do-chung">Sơ đồ đường đi phiếu VPP</a>. Mỗi mục bên dưới cũng có nút
        <b>Trên sơ đồ chung</b> dẫn về đúng hàng của bước đó.</div>
</div>

<h3>Các nhánh phụ</h3>
<ul>
    <li><a href="#de-nghi-vpp">Đề nghị VPP đến HC</a>: cách lấy VPP thường dùng nhất - xin từ kho VPP của phòng Hành Chánh.</li>
    <li><a href="#lien-phong-ban">Đề nghị liên phòng ban</a>: xin VPP từ kho của một phòng khác (không phải Hành Chánh).</li>
    <li><a href="#cach-ly">Cách ly - Loại bỏ - Huỷ</a>: xử lý VPP hỏng, hết hạn.</li>
    <li><a href="#danh-sach-chu-ky">Danh sách theo chu kỳ</a>: hệ thống tự tạo đề nghị hoặc phiếu dự trù định kỳ.</li>
</ul>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Hầu hết màn hình VPP làm việc trên <b>phòng ban đang chọn</b> ở thanh trên cùng. Dữ liệu nền (tên VPP, đơn
        vị tính, nhà sản xuất, định khu, phân loại của phòng) khai ở menu Dữ Liệu Gốc. Xem
        <a href="{{ route('pages.userGuide.show', 'materData') }}">Hướng dẫn Dữ Liệu Gốc</a>.</div>
</div>
