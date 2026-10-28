<p>
    Module <b>Hoá Chất</b> quản lý hoá chất của từng phòng ban từ lúc khai danh mục tới lúc dùng hết hoặc huỷ. Khác vật tư,
    hoá chất được <b>lấy dùng trực tiếp</b> (không cần đề nghị và ký duyệt nội bộ), nhưng có thêm các kiểm soát theo pháp luật:
    phân loại Nghị định 24/2026/NĐ-CP, hoá chất cấm, ngưỡng tồn trữ Phụ lục IV, tương kỵ khi lưu trữ và hạn dùng nội bộ.
</p>

<h3>Vòng đời một hoá chất</h3>
<div class="ug-flow">
    <div class="ug-flow-step">
        <span class="ug-flow-no">1</span>
        <b>Khai danh mục</b>
        <a href="#danh-muc-cong-ty">Danh mục công ty</a> → <a href="#hoa-chat-cua-phong">Hoá chất của phòng</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">2</span>
        <b>Nhập kho</b>
        <a href="#nhap-hoa-chat">Tạo phiếu nhập</a> → kiểm tra Đạt thì mới vào tồn
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">3</span>
        <b>Xác định hạn nội bộ</b>
        <a href="#han-dung">Khi mở nắp</a> (nếu danh mục có hạn dùng mặc định)
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">4</span>
        <b>Sử dụng</b>
        <a href="#su-dung">Quét mã lô, ghi số lượng dùng</a>, trừ tồn ngay
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">5</span>
        <b>Theo dõi</b>
        <a href="#ton-kho">Tồn kho</a>, <a href="#han-dung">cận hạn</a>, <a href="#nguong-pl-iv">ngưỡng PL IV</a>
    </div>
    <div class="ug-flow-step">
        <span class="ug-flow-no">6</span>
        <b>Bổ sung / xử lý</b>
        <a href="#du-tru">Dự trù</a> khi thiếu; <a href="#cach-ly">cách ly - huỷ</a> khi hỏng, hết hạn
    </div>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Dữ liệu nền khai ở menu Dữ Liệu Gốc: <b>Đơn Chất</b>, <b>Nhóm Nguy Hại Bảng B</b>, <b>Hoá Chất</b> (tên),
        Nhà Sản Xuất, Nhà Cung Cấp, Đơn Vị Tính, Điều Kiện Bảo Quản, Định Khu. Xem
        <a href="{{ route('pages.userGuide.show', 'materData') }}">Hướng dẫn Dữ Liệu Gốc</a>. Đề nghị, ký duyệt và dự trù nói chung
        giống module vật tư, xem thêm <a href="{{ route('pages.userGuide.show', 'material') }}">Hướng dẫn Vật Tư</a>.</div>
</div>
