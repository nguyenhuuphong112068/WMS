<p>Tồn <b>tính tự động</b> từ phiếu nhập, cân đối, phiếu sử dụng và phần <a href="#cach-ly">cách ly / loại bỏ</a> (cột Huỷ trong kỳ). Chọn <b>Từ ngày - Đến ngày</b> (mặc định trọn tháng
    hiện tại) để xem Tồn Đầu Kỳ, Nhập, Sử Dụng, Huỷ trong kỳ và Tồn Cuối Kỳ.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/ton-kho.png', 'caption' => 'Màn hình Tồn Kho › Chất Chuẩn › tab Theo mã ống chuẩn / Theo chất chuẩn / Theo định khu'])

<ul>
    <li><b>Theo mã ống chuẩn</b>: mỗi ống một dòng gọn như Tồn Kho Hoá Chất - cột <b>Đơn Vị</b> riêng, các ô số chỉ hiện con số
        (bấm để xem chi tiết). Thông tin phụ nằm ở tooltip khi rê chuột: ngày nhập / số cân đối (Nhập Trong Kỳ), số lần xuất (Sử Dụng),
        % đã dùng luỹ kế (Tồn Cuối Kỳ), số lô · số ống (Tổng Tồn), số ngày còn / quá hạn (Hạn Dùng - ngày tô đỏ khi đã quá hạn).
        Cột Thao Tác có nút <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-biohazard"></i></span> Cách ly ống hỏng / hết hạn
        và <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-hourglass-half"></i></span> Xác định hạn nội bộ; cột
        <i class="fas fa-paperclip"></i> là file đính kèm.</li>
    <li><b>Theo chất chuẩn</b>: gộp các ống theo chất chuẩn - số ống còn tồn / tổng số ống, cột Đơn Vị riêng, tồn theo kỳ và hạn gần nhất.</li>
    <li><b>Theo định khu</b>: sơ đồ kho - kệ - vị trí đang chứa ống nào.
        Nút <b>Thẻ | 3D | Mặt bằng</b> ở góc phải đổi kiểu xem. Chế độ <b>3D</b>: chọn kệ/tủ ở danh sách bên trái, kệ hiện 3D bên phải đúng lưới đã khai ở <b>Định Khu</b>; vị trí đang có hàng là <b>thùng hàng nổi lên</b>, tô màu theo <i>Tình trạng</i> (xanh: có hàng, vàng: cần chú ý, đỏ: hết hạn / âm kho) hoặc theo <i>Loại định khu</i>; vị trí trống là ô phẳng. Kéo chuột để xoay; các nút <b>Nghiêng</b>, <b>Chính diện</b>, <b>Từ trên</b> đổi góc nhìn. Bộ lọc tình trạng, loại định khu và ô tìm kiếm làm mờ các vị trí không khớp, danh sách kệ hiện số vị trí khớp và tự chuyển sang kệ có kết quả (ví dụ gõ tên chất chuẩn để thấy ngay nó nằm ở kệ, tầng, ô nào). Bấm thùng hàng để xem các ống chuẩn đang nằm ở đó. Nút <b>Mặt bằng</b>: xem cả kho/phòng theo mặt bằng đã khai ở <b>Dữ Liệu Gốc › Định Khu</b> (kho chưa khai mặt bằng thì các kệ được xếp tạm thành một hàng). Bên trái là danh sách kho/phòng và sơ đồ mặt bằng thu nhỏ, bên phải là 3D cả phòng, chỉ vẽ thùng hàng ở các vị trí đang có hàng. Khi lọc hoặc tìm kiếm, thùng khớp tô <b>xanh ngọc</b>, kệ chứa nó sáng viền và ghi số vị trí khớp, kho / kệ không khớp mờ đi, tự chuyển sang kho có kết quả. Bấm một kệ trên sơ đồ thu nhỏ hoặc trên 3D (ngoài thùng hàng) để xem 3D riêng kệ đó. Khi <b>tìm kiếm</b>, các thùng khớp <b>nhấp nháy</b> và khung 3D tự <b>phóng tới</b> chỗ đó; xoá từ khoá thì khung trở về toàn cảnh. Có thể gõ, dùng máy quét cầm tay, hoặc bấm nút <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-camera"></i></span> cạnh ô tìm để <b>quét mã QR / mã vạch trên nhãn bằng camera</b> (điện thoại, máy tính bảng, webcam). Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ; nếu không, nút camera bị khoá. Khung 3D và Mặt bằng cao tới gần đáy cửa sổ. Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.</li>
</ul>

<h3>Cân đối</h3>
<p>Nhập <b>Số Lượng Cân Đối</b> là số điều chỉnh (+ hoặc −) để đưa tồn về đúng thực tế, ví dụ sau khi xuất vượt làm tồn âm.</p>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Tổng mọi lần cân đối của một ống <b>không vượt quá 5% lượng nhập</b>, và cân đối xong tồn <b>không được âm</b>.</div>
</div>
