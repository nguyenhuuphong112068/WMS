<p>Tồn được <b>tính tự động</b> từ phiếu nhập, cân đối và các lần xuất. Không có chỗ nhập tồn bằng tay. Chọn
    <b>Từ ngày - Đến ngày</b> (mặc định trọn tháng hiện tại) để xem theo kỳ.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/material/ton-kho.png', 'caption' => 'Màn hình Tồn Kho › Vật Tư › tab Theo mã xuất nhập / Tồn theo mã vật tư / Tồn kho theo vị trí'])

<h3>Ba tab xem tồn</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 190px">Tab</th>
                <th>Nội dung</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Theo mã xuất nhập</td>
                <td style="font-weight: 400">Từng lô: <b>Tồn Đầu Kỳ</b>, <b>Nhập Trong Kỳ</b>, <b>Sử Dụng</b>, <b>Cách Ly/Huỷ</b>,
                    <b>Tồn Cuối Kỳ</b>, hạn dùng, vị trí. Chỉ hiện lô còn tồn hoặc có phát sinh trong kỳ. Có nút Cân đối và Biểu đồ.</td>
            </tr>
            <tr>
                <td>Tồn theo mã vật tư</td>
                <td style="font-weight: 400">Gộp các lô theo vật tư: <b>Tổng Tồn Vật Tư</b>, hạn gần nhất, nhà sản xuất. Lọc được theo
                    phân loại vật tư.</td>
            </tr>
            <tr>
                <td>Tồn kho theo vị trí</td>
                <td style="font-weight: 400">Sơ đồ kho, kệ, vị trí giống <b>Định Khu</b>; bấm vào vị trí để xem đang có lô nào. Tìm
                    nhanh theo vị trí, kệ/tủ hoặc tên vật tư.
                    Nút <b>Thẻ | 3D | Mặt bằng</b> ở góc phải đổi kiểu xem. Chế độ <b>3D</b>: chọn kệ/tủ ở danh sách bên trái, kệ hiện 3D bên phải đúng lưới đã khai ở <b>Định Khu</b>; vị trí đang có hàng là <b>thùng hàng nổi lên</b>, tô màu theo <i>Tình trạng</i> (xanh: có hàng, vàng: cần chú ý, đỏ: hết hạn / âm kho) hoặc theo <i>Loại định khu</i>; vị trí trống là ô phẳng. Trên nóc mỗi cột có tên kèm <b>mã cột</b>, trên mặt ô / nắp thùng in <b>mã vị trí</b> (đoạn cuối của mã, ví dụ <span class="ug-key">A1</span>). Kéo chuột để xoay; các nút <b>Nghiêng</b>, <b>Chính diện</b>, <b>Từ trên</b> đổi góc nhìn. Bộ lọc tình trạng, loại định khu và ô tìm kiếm làm mờ các vị trí không khớp, danh sách kệ hiện số vị trí khớp và tự chuyển sang kệ có kết quả (ví dụ gõ tên vật tư để thấy ngay nó nằm ở kệ, tầng, ô nào). Bấm thùng hàng để xem các mã xuất nhập đang nằm ở đó. Nút <b>Mặt bằng</b>: xem cả kho/phòng theo mặt bằng đã khai ở <b>Dữ Liệu Gốc › Định Khu</b> (kho chưa khai mặt bằng thì các kệ được xếp tạm thành một hàng). Bên trái là danh sách kho/phòng và sơ đồ mặt bằng thu nhỏ, bên phải là 3D cả phòng, chỉ vẽ thùng hàng ở các vị trí đang có hàng; nút <b><i class="fas fa-tags"></i> Nhãn vị trí</b> (mặc định bật, máy nhớ lựa chọn) vẽ đủ mọi ô của tủ, kể cả ô trống, và in <b>mã vị trí</b> (A1, B1, C2…) trên từng ô / nắp thùng như 3D ở Định Khu - nhìn toàn cảnh chữ nhỏ, phóng to hoặc đi bộ (Xuyên tủ) lại gần để đọc. Khi lọc hoặc tìm kiếm, thùng khớp tô <b>xanh ngọc</b>, kệ chứa nó sáng viền và ghi số vị trí khớp, kho / kệ không khớp mờ đi, tự chuyển sang kho có kết quả. <b>Nhấp đúp</b> một kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hàng) để xem 3D riêng kệ đó (nhấp một lần không chuyển, để lúc xoay hay đi Xuyên tủ lỡ bấm trúng tủ không bị đổi khung). Khi <b>tìm kiếm</b>, các thùng khớp <b>nhấp nháy</b>; ở chế độ 3D khung tự <b>phóng tới</b> chỗ đó (xoá từ khoá thì trở về toàn cảnh), ở chế độ Mặt bằng khung giữ nguyên toàn cảnh và trên đầu mỗi kệ có hàng khớp hiện <b>thẻ chi tiết</b>: tên, mã xuất nhập, tồn, vị trí (tối đa 4 dòng, còn lại ghi "+ N hàng hoá khớp khác"). Nhãn tên kệ và thẻ chi tiết luôn <b>quay mặt về phía người xem</b> khi xoay khung, chữ giữ nguyên cỡ khi phóng to / thu nhỏ. Nút <b><i class="fas fa-walking"></i> Xuyên tủ</b> (chỉ ở Mặt bằng) cho <b>đi bộ trong kho như game 3D</b>: bắt đầu đứng ngoài cửa phòng, mắt cao 1,6 m, không phóng to. Cạnh nút có <b>80° / 100° / 120°</b> để chọn độ rộng góc nhìn như camera góc rộng (mặc định 100°; số càng lớn càng thấy rộng, phòng nhỏ đỡ cảm giác chật; máy nhớ lựa chọn). <span class="ug-key">W</span> <span class="ug-key">A</span> <span class="ug-key">S</span> <span class="ug-key">D</span> (hoặc phím mũi tên) để đi tới / lùi / sang trái / sang phải, giữ <span class="ug-key">Shift</span> để chạy, lăn chuột để bước tới / lùi; <b>bấm vào khung</b> để khoá chuột rồi rê chuột để nhìn quanh (hoặc kéo chuột), <span class="ug-key">Esc</span> thả chuột. Đi xuyên qua được các tủ (tủ đã đi qua ra sau lưng nên không che), tâm ngắm <b>+</b> ở giữa đổi màu khi nhắm trúng thùng hàng, lúc đang khoá chuột bấm sẽ mở chi tiết thùng đó. Phím chỉ có tác dụng khi chuột đang ở trên khung (hoặc khung đang mở to); bấm lại nút <b>Xuyên tủ</b> hoặc một nút góc nhìn để quay ra toàn cảnh. Giữ <span class="ug-key">Shift</span> + <b>lăn chuột</b> trên khung để phóng to / thu nhỏ quanh con trỏ (lăn chuột không giữ Shift vẫn cuộn trang). Khung 3D <b>đổi cỡ được</b>: kéo thanh nhỏ ở mép dưới khung để đổi chiều cao, kéo thanh dọc giữa danh sách kệ/tủ và khung để đổi bề ngang danh sách (kéo hết sang trái thì ẩn danh sách); máy nhớ cỡ đã kéo, nhấp đúp thanh kéo để trả về mặc định. Phòng có hình dạng đa giác (khai ở Định Khu) được vẽ đúng hình. Có thể gõ, dùng máy quét cầm tay, hoặc bấm nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> cạnh ô tìm để <b>quét mã QR / mã vạch trên nhãn bằng camera</b> (điện thoại, máy tính bảng, webcam). Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ; nếu không, nút camera bị khoá. Khung 3D và Mặt bằng cao tới gần đáy cửa sổ. Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột (khung thường: <span class="ug-key">Shift</span> + lăn chuột) để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Cân đối số lượng</h3>
<p>Dùng khi tồn sổ sách của một lô lệch so với thực tế (sai số cân, hao hụt, đếm nhầm lúc nhập).</p>
<ol class="ug-steps">
    <li>Ở tab Theo mã xuất nhập, bấm nút <b>Cân đối</b> trên dòng.</li>
    <li>Nhập <b>Số Lượng Cân Đối</b> là <b>số điều chỉnh</b> (+ hoặc −), ví dụ <span class="ug-key">0.5</span> hay
        <span class="ug-key">-0.5</span>, không phải số tồn mới. Chọn <b>Thời Điểm Cân Đối</b> (mặc định là lúc mở form), ghi chú.</li>
    <li>Lưu. Bấm nút lịch sử trên dòng để xem các lần cân đối trước.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Tổng mọi lần cân đối của một lô <b>không vượt quá 5% lượng nhập</b>, và cân đối xong tồn <b>không được âm</b>. Lệch nhiều
        hơn thì cần làm rõ nguyên nhân, không xử lý bằng cân đối.</div>
</div>

<p class="mb-0"><b>Biểu đồ</b>: xem diễn biến nhập - xuất - tồn của một vật tư theo thời gian.</p>
