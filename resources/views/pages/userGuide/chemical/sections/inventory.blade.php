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
                    Chế độ <b>Thẻ</b> vẽ theo kiểu lưới của <b>Dữ Liệu Gốc › Định Khu › Cấu trúc kho</b>: các kệ/tủ xếp ngang cạnh nhau, mỗi cột/cánh một khối, mỗi hàng là một <b>tầng</b>, mỗi tầng là lưới ô <b>Ngang</b> (A, B…) × <b>Sâu</b> (1 = sát cửa). Ô có hàng tô màu tình trạng (xanh: có hàng, cam: cần chú ý, đỏ: hết hạn / vượt), số trong ô là số mã đang nằm đó, vạch dưới đáy ô là màu loại định khu; rê chuột lên ô để xem mã vị trí và hoá chất. Bộ lọc làm mờ ô không khớp, ẩn tầng / cột / kệ không còn ô khớp (xem <a href="#ton-kho-the">Thêm hoá chất & Đổi vị trí</a>).
                    Nút <b>Thẻ | 3D | Mặt bằng</b> ở góc phải đổi kiểu xem. Chế độ <b>3D</b>: chọn kệ/tủ ở danh sách bên trái, kệ hiện 3D bên phải đúng lưới đã khai ở <b>Định Khu</b>; vị trí đang có hàng là <b>thùng hàng nổi lên</b>, tô màu theo <i>Tình trạng</i> (xanh: có hàng, vàng: cần chú ý, đỏ: hết hạn / âm kho) hoặc theo <i>Loại định khu</i>; vị trí trống là ô phẳng. Trên nóc mỗi cột có tên kèm <b>mã cột</b>, trên mặt ô / nắp thùng in <b>mã vị trí</b> (đoạn cuối của mã, ví dụ <span class="ug-key">A1</span>). Kéo chuột để xoay; các nút <b>Nghiêng</b>, <b>Chính diện</b>, <b>Từ trên</b> đổi góc nhìn. Bộ lọc tình trạng, loại định khu và ô tìm kiếm làm mờ các vị trí không khớp, danh sách kệ hiện số vị trí khớp và tự chuyển sang kệ có kết quả (ví dụ gõ tên hoá chất để thấy ngay nó nằm ở kệ, tầng, ô nào). Bấm thùng hàng để xem các mã xuất nhập đang nằm ở đó. <b>Tương kỵ cùng tầng</b>: vị trí có lô nằm chung tầng với hoá chất tương kỵ
                    (Sơ đồ lưu trữ GHS) có <b>viền đỏ</b> và dấu <b>!</b> đỏ (thẻ ở chế độ Thẻ, nắp thùng ở 3D); dòng tiêu đề và danh sách kệ bên trái ghi
                    <i>N vị trí tương kỵ</i>; ở Mặt bằng, kệ có tương kỵ tô đỏ cả trên 3D lẫn sơ đồ thu nhỏ, nhãn kệ đổi đỏ. Chip lọc
                    <span class="ug-chip is-danger">Tương kỵ</span> (kèm số vị trí) chỉ để lại các vị trí đó. Rê chuột vào thùng / dấu ! để xem
                    tương kỵ với hoá chất nào; hộp chi tiết vị trí có khối đỏ <b>Kết luận</b>: "N cặp hoá chất tương kỵ nằm cùng tầng - nên tách ra tầng khác", mỗi cặp một dòng: <i>Hoá chất A (vị trí) có thuộc tính [chip…] tương kỵ với thuộc tính [chip…] của Hoá chất B (vị trí)</i>, kèm dòng <i>Chi tiết</i> nêu từng cặp thuộc tính. Nút <b>Mặt bằng</b>: xem cả kho/phòng theo mặt bằng đã khai ở <b>Dữ Liệu Gốc › Định Khu</b> (kho chưa khai mặt bằng thì các kệ được xếp tạm thành một hàng). Bên trái là danh sách kho/phòng và sơ đồ mặt bằng thu nhỏ, bên phải là 3D cả phòng, chỉ vẽ thùng hàng ở các vị trí đang có hàng; nút <b><i class="fas fa-tags"></i> Nhãn vị trí</b> (mặc định bật, máy nhớ lựa chọn) vẽ đủ mọi ô của tủ, kể cả ô trống, và in <b>mã vị trí</b> (A1, B1, C2…) trên từng ô / nắp thùng như 3D ở Định Khu - nhìn toàn cảnh chữ nhỏ, phóng to hoặc bấm Di chuyển vào kho đi lại gần để đọc. Khi lọc hoặc tìm kiếm, thùng khớp tô <b>xanh ngọc</b>, kệ chứa nó sáng viền và ghi số vị trí khớp, kho / kệ không khớp mờ đi, tự chuyển sang kho có kết quả. <b>Nhấp đúp</b> một kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hàng) để xem 3D riêng kệ đó (nhấp một lần không chuyển, để lúc xoay hay đi Di chuyển vào kho lỡ bấm trúng tủ không bị đổi khung). Khi <b>tìm kiếm</b>, các thùng khớp <b>nhấp nháy</b>; ở chế độ 3D khung tự <b>phóng tới</b> chỗ đó (xoá từ khoá thì trở về toàn cảnh), ở chế độ Mặt bằng khung giữ nguyên toàn cảnh và trên đầu mỗi kệ có hàng khớp hiện <b>thẻ chi tiết</b>: tên, mã xuất nhập, tồn, vị trí (tối đa 4 dòng, còn lại ghi "+ N hàng hoá khớp khác"). Nhãn tên kệ và thẻ chi tiết luôn <b>quay mặt về phía người xem</b> khi xoay khung, chữ giữ nguyên cỡ khi phóng to / thu nhỏ. Nút <b><i class="fas fa-walking"></i> Di chuyển vào kho</b> (chỉ ở Mặt bằng) cho <b>đi bộ trong kho như game 3D</b>: bắt đầu đứng ngoài cửa phòng, mắt cao 1,6 m, không phóng to. Cạnh nút có <b>80° / 100° / 120°</b> để chọn độ rộng góc nhìn như camera góc rộng (mặc định 100°; số càng lớn càng thấy rộng, phòng nhỏ đỡ cảm giác chật; máy nhớ lựa chọn). <span class="ug-key">W</span> <span class="ug-key">A</span> <span class="ug-key">S</span> <span class="ug-key">D</span> (hoặc phím mũi tên) để đi tới / lùi / sang trái / sang phải, <span class="ug-key">Space</span> / <span class="ug-key">E</span> bay lên, <span class="ug-key">C</span> / <span class="ug-key">Q</span> hạ xuống (tầm mắt từ 0,3 m tới trên nóc tủ cao nhất 1 m), giữ <span class="ug-key">Shift</span> để chạy, lăn chuột để bước tới / lùi; <b>bấm vào khung</b> để khoá chuột rồi rê chuột để nhìn quanh (hoặc kéo chuột), <span class="ug-key">Esc</span> thả chuột. Đi xuyên qua được các tủ (tủ đã đi qua ra sau lưng nên không che), tâm ngắm <b>+</b> ở giữa đổi màu khi nhắm trúng thùng hàng, lúc đang khoá chuột bấm sẽ mở chi tiết thùng đó. Phím chỉ có tác dụng khi chuột đang ở trên khung (hoặc khung đang mở to); bấm lại nút <b>Di chuyển vào kho</b> hoặc một nút góc nhìn để quay ra toàn cảnh. Giữ <span class="ug-key">Shift</span> + <b>lăn chuột</b> trên khung để phóng to / thu nhỏ quanh con trỏ (lăn chuột không giữ Shift vẫn cuộn trang). Khung 3D <b>đổi cỡ được</b>: kéo thanh nhỏ ở mép dưới khung để đổi chiều cao, kéo thanh dọc giữa danh sách kệ/tủ và khung để đổi bề ngang danh sách (kéo hết sang trái thì ẩn danh sách); máy nhớ cỡ đã kéo, nhấp đúp thanh kéo để trả về mặc định. Phòng có hình dạng đa giác (khai ở Định Khu) được vẽ đúng hình. Có thể gõ, dùng máy quét cầm tay, hoặc bấm nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> cạnh ô tìm để <b>quét mã QR / mã vạch trên nhãn bằng camera</b> (điện thoại, máy tính bảng, webcam). Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ; nếu không, nút camera bị khoá. Khung 3D và Mặt bằng cao tới gần đáy cửa sổ. Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột (khung thường: <span class="ug-key">Shift</span> + lăn chuột) để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.</td>
            </tr>
        </tbody>
    </table>
</div>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-3d.png', 'caption' => 'Tab Theo định khu ở chế độ 3D: thùng hàng là vị trí đang có hàng'])
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-mat-bang.png', 'caption' => 'Chế độ Mặt bằng: tìm hoá chất, thùng khớp tô xanh ngọc và nhấp nháy, trên đầu kệ hiện thẻ chi tiết (tên, mã xuất nhập, tồn, vị trí); thanh kéo dưới khung đổi chiều cao'])

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
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/ton-kho-dinh-khu-nhanh.png', 'caption' => 'Hộp thoại Định Khu Nhanh: vị trí tương kỵ có lý do cảnh báo màu đỏ (vẫn chọn được)'])
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        Chỉ chọn được vị trí <b>Dự Phòng</b>, <b>Sử Dụng</b> hoặc chưa phân loại - khu Biệt Trữ, Cách Ly Chờ Quyết Định, Loại Bỏ/Chờ Huỷ
        không nhận lô đã nhập kho. Vị trí cùng <b>{{ \App\Support\ChemicalCompatibility::scope()['label'] }}</b> với hoá chất tương kỵ
        (Sơ đồ lưu trữ GHS) có lý do cảnh báo màu đỏ nhưng <b>vẫn chọn được</b>; lưu xong hệ thống hiện hộp <b>Đã lưu - có tương kỵ</b>
        nêu từng hoá chất tương kỵ (tính cả các mã vừa xếp cùng lượt).
        Mỗi lần tối đa {{ \App\Http\Controllers\Pages\Inventory\ChemicalInventoryController::ASSIGN_MAX }} mã. Định khu nhanh chỉ dành cho
        mã chưa có vị trí; đổi chỗ mã đã có vị trí thì dùng <a href="#ton-kho-the">Đổi vị trí</a> (ghi lý do).
        Cần quyền <b>Định Khu Nhanh Hoá Chất</b>, không có quyền thì thẻ chỉ để xem.
    </div>
</div>
<h3 id="ton-kho-the">Thêm hoá chất & Đổi vị trí trên lưới</h3>
<ol class="ug-steps">
    <li>Bấm một ô trên lưới (kể cả ô trống, nếu có quyền) để mở hộp chi tiết vị trí. Nút <b>Thêm hoá chất</b> mở hộp chọn nhiều mã
        đang <b>Chưa xếp vị trí</b> (có ô tìm, từng mã có cảnh báo nếu tương kỵ với hoá chất cùng tầng) để xếp vào ô này.</li>
    <li>Muốn dời mã đã có vị trí: tick các mã ở cột chọn trong hộp chi tiết rồi bấm <b>Đổi vị trí N mã đã chọn</b>. Hộp <b>Đổi Vị Trí</b>
        bắt chọn <b>Vị trí mới</b> và ghi <b>Lý do</b>; mỗi lần đổi ghi vào lịch sử phiếu nhập và Audit Trail (vị trí cũ → mới, lý do).</li>
    <li><b>Kéo thả</b>: kéo thẻ ở khối Chưa xếp vị trí (hoặc các thẻ đang chọn) thả vào một ô để định khu (hệ thống hỏi lại, cảnh báo
        tương kỵ); kéo một ô có hàng thả sang ô khác để mở hộp Đổi Vị Trí điền sẵn vị trí mới.</li>
    <li><b>Chọn nhiều ô</b>: giữ <span class="ug-key">Shift</span> và kéo chuột quét một khung trên sơ đồ, hoặc <span class="ug-key">Shift</span> /
        <span class="ug-key">Ctrl</span> + bấm từng ô (ô chọn viền vàng, <span class="ug-key">Esc</span> bỏ chọn). Thanh nổi
        <i>Đã chọn N ô · M mã</i> có nút <b>Đổi vị trí</b> (gộp tất cả vào một vị trí) và <b>Bỏ chọn</b>. Kéo một ô đã chọn thả vào ô đích thì
        hộp Đổi Vị Trí cho chọn: <b>Dời nguyên khối</b> (mỗi ô sang ô tương ứng theo cùng độ lệch cột / tầng / ngang / sâu; báo
        "không đủ chỗ" nếu ra ngoài kệ hoặc rơi vào khu cách ly) hoặc <b>Gộp vào một vị trí</b>. Bảng trong hộp có cột Vị trí hiện tại / Vị trí mới.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Ô thuộc khu Biệt Trữ, Cách Ly Chờ Quyết Định, Loại Bỏ không thêm / đổi được. Cần quyền <b>Định Khu Nhanh Hoá Chất</b>; Tồn Kho
        Chất Chuẩn dùng cùng lưới nhưng chỉ để xem.</div>
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
