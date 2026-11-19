{{-- Đoạn dùng chung 4 tài liệu (mục Khái niệm): nút "Chọn trên 3D" cạnh mọi ô chọn định khu (pages.shared.locationPicker3d).
     $ugCompat = true (Hoá chất) thì nói thêm về cảnh báo tương kỵ. --}}
<h4>Chọn định khu trên 3D</h4>
<p>Cạnh nhãn của mọi ô chọn định khu có nút <span class="btn btn-sm ug-ui" style="border:1px solid var(--primary);color:var(--primary);background:#fff;padding:0 8px;font-size:.72rem"><i class="fas fa-cube mr-1"></i>Chọn trên 3D</span>
    (ô nằm trong bảng thì nút nhỏ <b>3D</b> ngay dưới ô). Bấm nút để chọn vị trí trên mô hình kệ/tủ thay vì đọc mã:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/chon-dinh-khu-3d.png', 'caption' => 'Chọn định khu trên 3D: đang xem riêng Tầng 03, ô xanh dương là vị trí vừa chọn'])
<ol class="ug-steps">
    <li>Chọn <b>kệ/tủ</b> ở danh sách bên trái (gom theo kho/phòng, số bên cạnh là số vị trí chọn được). Ô đang có giá trị thì mở thẳng kệ chứa vị trí đó.</li>
    <li>Kéo chuột để xoay; các nút <b>Nghiêng / Chính diện / Từ trên</b> và <b>Trái / Phải / Sau</b> đổi nhanh mặt nhìn, nút kính lúp phóng to.
        Bấm được vào ô từ bất kỳ phía nào - vách kệ không chắn.</li>
    <li>Kệ nhiều tầng: bấm chip <b>Tầng 01, 02...</b> để chỉ dựng đúng tầng đó (tầng trên không che tầng dưới), <b>Tất cả</b> để xem lại cả kệ.</li>
    <li>Bấm vào một ô: ô tô xanh dương, dưới khung hiện đường dẫn đầy đủ (Kho / Kệ / Cột / Tầng / Mã vị trí). Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-check mr-1"></i>Chọn vị trí</span> để điền vào ô chọn.</li>
</ol>
<p>Màu ô: <span class="ug-chip" style="background:#86D19A;color:#14532d">Trống</span>
    <span class="ug-chip" style="background:#F59E0B;color:#fff">Đang có hàng</span>
    <span class="ug-chip" style="background:#2563EB;color:#fff">Đang chọn</span>
    @if (! empty($ugCompat))<span class="ug-chip" style="background:#fff;border:2px solid #DC2626;color:#DC2626">Tương kỵ cùng tầng</span>@endif
    <span class="ug-chip" style="background:#E2E8F0;color:#64748b">Không chọn được</span>.
    Ô có hàng được vẽ phẳng để không che ô phía sau; bật <b><i class="fas fa-box"></i> Hiện thùng hàng</b> nếu muốn thấy thùng nổi.</p>
<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Ô <b>Đang có hàng</b> vẫn chọn được - để hàng mới chung vị trí với hàng cũ (dòng dưới khung báo số lô đang có).
        Ô <b>Không chọn được</b> là vị trí không hợp với bước đang làm (vd. khi nhập mới chỉ chọn được khu Biệt Trữ).
        @if (! empty($ugCompat)) Ô viền đỏ nằm cùng tầng với hoá chất tương kỵ với hoá chất đang chọn trên phiếu: chỉ cảnh báo, vẫn chọn được. @endif
        Esc hoặc <b>Huỷ</b> chỉ đóng khung 3D, phiếu đang nhập giữ nguyên.</div>
</div>
