<p>Máy chủ WMS chạy <b>HTTP</b> trong mạng nội bộ, mà Chrome chỉ cho trang web mở <b>camera</b> khi trang chạy HTTPS hoặc mở ngay
    trên máy chủ. Trang <b>Cài Đặt Tablet Quét Mã</b> là trợ lý giúp Admin cài một lần cho mỗi tablet (đang dùng Samsung Galaxy Tab),
    để nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> quét nhãn QR / mã vạch bằng camera
    chạy được trên WMS. Trang chỉ dành cho <b>Admin</b>, mở ở mục <b>Cài Đặt Tablet</b> trên menu trái.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/cai-dat-tablet.png', 'caption' => 'Trang Cài Đặt Tablet Quét Mã'])

<h3>Trang gồm</h3>
<ul>
    <li><b>Trạng Thái Thiết Bị Này</b>: 4 dòng tự kiểm tra (trình duyệt, kết nối được coi là an toàn, có camera, quyền camera).
        Cả 4 dòng xanh là tablet đã quét bằng camera được.</li>
    <li><b>Quét Thử</b> + <b>Mã Mẫu Để Thử</b>: mã QR (như nhãn vật tư / hoá chất / VPP) và mã vạch Code 128 (như nhãn chất chuẩn) để thử ngay.</li>
    <li><b>Các Bước Cài Đặt</b> và khối <b>Cài hàng loạt nhiều tablet</b> (Samsung Knox / MDM).</li>
</ul>

<h3>Cài cho một tablet (Chrome trên Android)</h3>
<ol class="ug-steps">
    <li>Mở trang này bằng <b>Google Chrome</b> (Samsung Internet không có mục cài đặt này): bấm <b>Mở trang này bằng Chrome</b>,
        hoặc quét mã QR trên trang bằng camera của tablet.</li>
    <li>Bấm <b>Sao chép</b> cạnh dòng <span class="ug-key">chrome://flags/#unsafely-treat-insecure-origin-as-secure</span>, dán vào
        thanh địa chỉ của Chrome rồi Enter.</li>
    <li>Ở mục <b>Insecure origins treated as secure</b> (tô vàng), dán địa chỉ server WMS mà trang đã sao chép sẵn, đổi
        <i>Disabled</i> thành <i>Enabled</i>. Mở WMS bằng nhiều địa chỉ (tên máy, IP khác) thì ghi đủ, cách nhau dấu phẩy.</li>
    <li>Bấm <b>Relaunch</b> để khởi động lại Chrome.</li>
    <li>Quay lại trang, 4 dòng trạng thái chuyển xanh là xong. Bấm <b>Quét bằng camera</b>, chọn <b>Cho phép</b> khi Chrome hỏi quyền
        camera rồi quét thử một mã mẫu.</li>
</ol>
<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Nhiều tablet thì cài qua phần mềm quản lý thiết bị (Samsung Knox / MDM): khai 2 chính sách Chrome trang đã ghi sẵn,
        tablet nhận cấu hình là dùng camera được ngay, không cần làm bước 2 - 4. Khối <b>Máy tính Windows (Chrome / Edge)</b> hướng dẫn
        cách làm tương tự khi quét bằng webcam trên máy tính.</div>
</div>

<h3 id="co-trang-tablet">Thu nhỏ / phóng to trang trên tablet</h3>
<p>Trên thiết bị cảm ứng (tablet, điện thoại), thanh trên cùng có cụm nút cỡ trang
    <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-minus"></i></span>
    <span class="btn btn-sm btn-outline-secondary ug-ui">100%</span>
    <span class="btn btn-sm btn-outline-secondary ug-ui"><i class="fas fa-plus"></i></span>
    (máy tính có chuột không hiện cụm này):</p>
<ul>
    <li><span class="ug-key">−</span> thu nhỏ trang (90%, 80%, 75%, 67%, 50%) để thấy nhiều cột của bảng hơn;
        <span class="ug-key">+</span> phóng to (110%, 125%, 150%) cho chữ dễ đọc.</li>
    <li>Chạm vào số <b>%</b> để về 100%.</li>
    <li>Cỡ trang được nhớ <b>riêng trên từng tablet</b>, áp cho mọi trang WMS mở trên máy đó.</li>
</ul>
