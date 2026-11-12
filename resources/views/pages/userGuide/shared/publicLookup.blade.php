{{--
| Mục "Tra Cứu Không Cần Đăng Nhập" dùng chung cho tài liệu Hoá Chất / Chất Chuẩn / Vật Tư.
| Tham số: $ugLookup = ['route' => tên route tra cứu, 'tab' => nhãn nút loại hàng, 'item' => tên loại hàng (chữ thường),
|                       'lot' => tên mã lô (chữ thường)]
| Trang tra cứu: General\PublicStockLookupController + public/stockLookup.blade.php.
--}}
<p>Trang <b>Tra cứu tồn kho</b> mở được <b>không cần đăng nhập</b>, từ nút <b>Tra cứu tồn kho</b> ở góc trên bên phải trang đăng
    nhập. Dùng khi cần biết nhanh một {{ $ugLookup['item'] }} đang nằm ở đâu. Đầu trang có 4 nút <b>Hoá chất | Chất chuẩn | Vật tư | VPP</b>
    để chuyển loại hàng (giữ nguyên phòng ban và từ khoá đang tra).</p>
<ol class="ug-steps">
    <li>Ở trang đăng nhập, bấm <b>Tra cứu tồn kho</b> rồi chọn nút <b>{{ $ugLookup['tab'] }}</b> (hoặc mở thẳng
        <a href="{{ route($ugLookup['route']) }}" target="_blank">trang tra cứu {{ $ugLookup['item'] }}</a>).</li>
    <li>Chọn <b>Phòng ban</b>, gõ tên, mã {{ $ugLookup['item'] }} hoặc {{ $ugLookup['lot'] }} (bỏ trống để xem toàn bộ) rồi bấm
        <b>Tra cứu</b>. Nút <i class="fas fa-camera"></i> cạnh ô dùng để <b>quét mã trên nhãn bằng camera</b> - quét xong tự tra cứu.</li>
    <li>Kết quả có nút <b>Thẻ | 3D | Mặt bằng</b> như tab Theo định khu của màn hình Tồn Kho: <b>Thẻ</b> liệt kê vị trí kèm số tồn,
        <b>3D</b> dựng từng kệ/tủ, <b>Mặt bằng</b> xem cả kho/phòng. Vị trí khớp từ khoá tô <b>xanh ngọc</b> và <b>nhấp nháy</b>;
        ở chế độ 3D khung tự phóng tới chỗ đó, ở chế độ Mặt bằng trên đầu kệ có hàng khớp hiện thẻ chi tiết (tên, mã, tồn, vị trí)
        luôn quay về phía người xem, nút <b><i class="fas fa-tags"></i> Nhãn vị trí</b> in mã ô (A1, B1…) trên mọi ô của tủ, nút <b><i class="fas fa-walking"></i> Di chuyển vào kho</b> để đi bộ trong kho như game 3D (W A S D đi, Space / E lên, C / Q xuống, Shift chạy, bấm vào khung rồi rê chuột để nhìn quanh, Esc thả chuột; đi xuyên qua tủ, không phóng to; nút 80° / 100° / 120° chọn độ rộng góc nhìn),
        <span class="ug-key">Shift</span> + lăn chuột để phóng to / thu nhỏ, kéo thanh ở mép dưới khung để đổi chiều cao. Bấm thẻ hoặc thùng hàng để xem chi tiết: tồn, số lô, {{ $ugLookup['lot'] }}, hạn gần nhất.
        Phòng ban chưa khai định khu chỉ có kiểu Thẻ.</li>
</ol>
<p class="mb-0">Trang chỉ đọc, chỉ tính lô đã kiểm tra nhập và còn tồn tới hiện tại. Viền đầu thẻ vị trí: <b>vàng</b> = có lô còn
    dưới 30 ngày hạn dùng, <b>đỏ</b> = có lô đã hết hạn. Camera chỉ mở được khi trang chạy qua HTTPS hoặc mở ngay trên máy chủ.</p>
