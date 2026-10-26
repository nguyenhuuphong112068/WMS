@include('pages.userGuide.shared.flowMapLink', ['lane' => 'cach-ly', 'no' => 4, 'label' => 'Cách ly - Loại bỏ - Huỷ'])

<p>Xử lý vật tư <b>hỏng, ẩm mốc, rách bao bì, hết hạn</b> theo 3 bước. Từ bước 1 hàng đã bị trừ tồn nên không ai cấp phát
    hay chuyển đi được nữa.</p>

<ol class="ug-steps">
    <li><b>Cách ly</b>: bấm <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-biohazard mr-1"></i> Cách ly vật tư hỏng</span>,
        chọn các mã xuất nhập (lọc được theo hạn dùng: đã hết hạn, sát hạn, cận hạn), số lượng, <b>Định khu Chờ Quyết Định</b>
        và <b>Lý do cách ly</b>.</li>
    <li><b>Quyết định</b> (quyền <i>Quyết Định Loại Bỏ Vật Tư Hỏng</i>), ghi căn cứ và nhập lại mật khẩu:
        <ul>
            <li><b>Loại bỏ</b>: hàng chuyển sang định khu <b>Loại Bỏ/Chờ Huỷ</b>, không thể quay lại kho.</li>
            <li><b>Trả về kho</b>: vật tư đạt, chọn định khu trả về; tồn được cộng lại và dùng bình thường.</li>
        </ul>
    </li>
    <li><b>Huỷ</b>: khi đã tiêu huỷ thực tế, chọn các mục rồi bấm
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-fire mr-1"></i> Huỷ mục đã chọn</span>, ghi phương pháp huỷ,
        ghi chú và nhập lại mật khẩu. Bước này không làm thay đổi tồn.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Vật tư <a href="#hieu-chuan">hiệu chuẩn Không đạt</a> tự vào bước Cách ly mà không cần thao tác. Định khu Chờ Quyết Định
        và Loại Bỏ/Chờ Huỷ là các vị trí đã gán phân loại tương ứng ở <b>Dữ Liệu Gốc › Định Khu</b>.</div>
</div>
