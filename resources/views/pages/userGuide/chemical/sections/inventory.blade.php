<p>Tồn được <b>tính tự động</b> từ phiếu nhập, cân đối và các lần xuất. Chọn <b>Từ ngày - Đến ngày</b> (mặc định trọn tháng hiện tại)
    để xem theo kỳ: Tồn Đầu Kỳ, Nhập / Cân Đối / Sử Dụng / Huỷ Trong Kỳ, Tồn Cuối Kỳ.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho.png', 'caption' => 'Màn hình Tồn Kho › Hoá Chất › tab Theo mã xuất nhập / Theo hoá chất / Theo định khu'])

<h3>Ba tab xem tồn</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 180px">Tab</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Theo mã xuất nhập</td>
                <td style="font-weight: 400">Từng lô: các chỉ số theo kỳ, hạn dùng, hạn nội bộ, vị trí thực tế. Trên dòng có nút
                    <b>Cân đối</b>, <b>Cách ly</b>, <b>Biểu đồ</b>, file đính kèm và xác định hạn nội bộ.</td>
            </tr>
            <tr>
                <td>Theo hoá chất</td>
                <td style="font-weight: 400">Gộp các lô theo hoá chất: tổng tồn, hạn gần nhất, tồn theo từng số lô.</td>
            </tr>
            <tr>
                <td>Theo định khu</td>
                <td style="font-weight: 400">Sơ đồ kho - kệ - vị trí, bấm vào vị trí để xem đang có lô nào.
                    Nút <b>Thẻ | 3D | Mặt bằng</b> ở góc phải đổi kiểu xem. Chế độ <b>3D</b>: chọn kệ/tủ ở danh sách bên trái, kệ hiện 3D bên phải đúng lưới đã khai ở <b>Định Khu</b>; vị trí đang có hàng là <b>thùng hàng nổi lên</b>, tô màu theo <i>Tình trạng</i> (xanh: có hàng, vàng: cần chú ý, đỏ: hết hạn / âm kho) hoặc theo <i>Loại định khu</i>; vị trí trống là ô phẳng. Kéo chuột để xoay; các nút <b>Nghiêng</b>, <b>Chính diện</b>, <b>Từ trên</b> đổi góc nhìn. Bộ lọc tình trạng, loại định khu và ô tìm kiếm làm mờ các vị trí không khớp, danh sách kệ hiện số vị trí khớp và tự chuyển sang kệ có kết quả (ví dụ gõ tên hoá chất để thấy ngay nó nằm ở kệ, tầng, ô nào). Bấm thùng hàng để xem các mã xuất nhập đang nằm ở đó. Nút <b>Mặt bằng</b>: xem cả kho/phòng theo mặt bằng đã khai ở <b>Dữ Liệu Gốc › Định Khu</b> (kho chưa khai mặt bằng thì các kệ được xếp tạm thành một hàng). Bên trái là danh sách kho/phòng và sơ đồ mặt bằng thu nhỏ, bên phải là 3D cả phòng, chỉ vẽ thùng hàng ở các vị trí đang có hàng. Khi lọc hoặc tìm kiếm, thùng khớp tô <b>xanh ngọc</b>, kệ chứa nó sáng viền và ghi số vị trí khớp, kho / kệ không khớp mờ đi, tự chuyển sang kho có kết quả. Bấm một kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hàng) để xem 3D riêng kệ đó. Khi <b>tìm kiếm</b>, các thùng khớp <b>nhấp nháy</b> và khung 3D tự <b>phóng tới</b> chỗ đó; xoá từ khoá thì khung trở về toàn cảnh. Có thể gõ, dùng máy quét cầm tay, hoặc bấm nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> cạnh ô tìm để <b>quét mã QR / mã vạch trên nhãn bằng camera</b> (điện thoại, máy tính bảng, webcam). Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ; nếu không, nút camera bị khoá. Khung 3D và Mặt bằng cao tới gần đáy cửa sổ. Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.</td>
            </tr>
        </tbody>
    </table>
</div>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-3d.png', 'caption' => 'Tab Theo định khu ở chế độ 3D: thùng hàng là vị trí đang có hàng'])
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-mat-bang.png', 'caption' => 'Chế độ Mặt bằng: tìm một hoá chất, khung 3D phóng tới các thùng khớp (tô xanh ngọc, nhấp nháy) và kệ chứa chúng'])

<h3>Mã chưa xếp vị trí & Định khu nhanh</h3>
<p>Ở tab <b>Theo định khu</b> (chế độ Thẻ), các mã xuất nhập còn tồn mà chưa có vị trí nằm trong khối <b>Chưa xếp vị trí</b> trên cùng.
    Bấm vào đầu khối để <b>thu gọn / mở</b> (máy bạn nhớ lựa chọn này); nút <b>Thu gọn tất cả</b> cũng thu gọn khối. Ô tìm kiếm lọc luôn
    các thẻ trong khối theo mã xuất nhập, tên hoá chất, mã hoá chất, số lô - số trên khối đổi thành <i>số khớp / tổng</i>.
    Ở chế độ 3D, bấm nhãn <b>N mã chưa xếp vị trí</b> trên đầu khung để về chế độ Thẻ và mở ngay khối này.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-chua-xep.png', 'caption' => 'Khối Chưa xếp vị trí: chọn thẻ rồi bấm Định khu N mã đã chọn, hoặc nút Định khu trên từng thẻ'])
<ol class="ug-steps">
    <li><b>Một mã</b>: bấm nút <b><i class="fas fa-map-marker-alt"></i> Định khu</b> ở góc dưới thẻ.
        <b>Nhiều mã vào cùng một vị trí</b>: bấm vào các thẻ để chọn (thẻ chuyển nền xanh), hoặc gõ tìm (ví dụ tên hoá chất) rồi bấm
        <span class="btn btn-sm btn-outline-primary ug-ui"><i class="far fa-check-square mr-1"></i> Chọn tất cả</span> để chọn mọi thẻ
        đang hiện; sau đó bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-map-marker-alt mr-1"></i> Định khu N mã đã chọn</span>.</li>
    <li>Hộp thoại <b>Định Khu Nhanh</b> liệt kê các mã sẽ xếp và mở sẵn danh sách <b>Vị trí lưu trữ</b>: gõ tên kho, kệ/tủ, cột, tầng
        hoặc mã vị trí để tìm; hàng chip màu lọc theo loại định khu, hàng chip <b>Kệ/Tủ</b> chỉ để lại vị trí của kệ/tủ
        cần cất (xem <a href="#khai-niem-chung">Chọn định khu</a>).</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-map-marker-alt mr-1"></i> Lưu định khu</span>. Thẻ rời khối
        Chưa xếp vị trí và hiện ở đúng ô trên sơ đồ; cột vị trí ở tab Theo mã xuất nhập cập nhật ngay, không cần tải lại trang. Mỗi mã
        được ghi một dòng <b>Định khu</b> vào lịch sử phiếu nhập.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-dinh-khu-nhanh.png', 'caption' => 'Hộp thoại Định Khu Nhanh: vị trí tương kỵ bị khoá, kèm lý do màu đỏ'])
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        Chỉ chọn được vị trí <b>Dự Phòng</b>, <b>Sử Dụng</b> hoặc chưa phân loại - khu Biệt Trữ, Cách Ly Chờ Quyết Định, Loại Bỏ/Chờ Huỷ
        không nhận lô đã nhập kho. Vị trí cùng <b>{{ \App\Support\ChemicalCompatibility::scope()['label'] }}</b> với hoá chất tương kỵ
        (Sơ đồ lưu trữ GHS) bị khoá; chọn nhiều mã mà có hai hoá chất tương kỵ nhau thì hệ thống báo lỗi và <b>không xếp mã nào</b>.
        Mỗi lần tối đa {{ \App\Http\Controllers\Pages\Inventory\ChemicalInventoryController::ASSIGN_MAX }} mã. Định khu nhanh chỉ dành cho
        mã chưa có vị trí; đổi chỗ mã đã có vị trí thì <b>Điều chỉnh</b> phiếu ở <a href="#so-nhap">Sổ Nhập Hoá Chất</a> (ghi lý do).
        Cần quyền <b>Định Khu Nhanh Hoá Chất</b>, không có quyền thì thẻ chỉ để xem.
    </div>
</div>
<p>Trạng thái tồn: <b>Còn hàng</b>, <b>Sắp hết</b> (dưới ngưỡng tối thiểu của phòng, hoặc dưới 20% lượng nhập nếu chưa đặt ngưỡng),
    <b>Hết hàng</b>, <b>Âm kho</b> (đã dùng vượt lượng nhập). Trạng thái hạn: <b>Sắp hết hạn</b> (còn dưới 30 ngày), <b>Hết hạn</b>. Ô lọc <b>Hoá chất VS</b> lọc theo thuộc tính hoá chất vi sinh của phiếu nhập, áp dụng cho mọi tab.</p>

<h3>Cân đối số lượng</h3>
<ol class="ug-steps">
    <li>Ở tab Theo mã xuất nhập, bấm <b>Cân đối</b> trên lô.</li>
    <li>Nhập <b>Số Lượng Cân Đối</b> là <b>số điều chỉnh</b> (+ hoặc −), không phải số tồn mới. Chọn <b>Thời Điểm Cân Đối</b>, ghi chú. Form
        hiện sẵn tổng đã cân đối và hạn mức còn lại.</li>
    <li>Lưu. Bấm nút lịch sử để xem các lần cân đối trước.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Tổng mọi lần cân đối của một lô <b>không vượt quá 5% lượng nhập</b>, và cân đối xong tồn <b>không được âm</b>. Cân đối luôn tính trên
        số liệu hôm nay, không phụ thuộc kỳ đang lọc.</div>
</div>
