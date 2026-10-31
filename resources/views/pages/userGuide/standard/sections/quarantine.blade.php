<p>Xử lý ống chuẩn <b>hỏng, hết hạn, nhiễm bẩn, có kết quả OOS</b> theo 3 bước. Từ bước 1 ống đã bị trừ tồn nên không cấp phát hay ghi
    sử dụng được nữa. Phiếu Sử dụng không còn loại <b>Loại bỏ</b>: mọi ống hỏng đều đi đường này.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/cach-ly.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Chất Chuẩn › tab Cách ly - Loại bỏ - Huỷ'])

<ol class="ug-steps">
    <li><b>Cách ly</b>: mở <b>Tồn Kho Chất Chuẩn</b> (tab Theo mã ống chuẩn hoặc Cận hạn dùng), bấm
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-biohazard"></i></span> ở cột Thao Tác của ống, nhập
        <b>Số lượng cách ly</b> (không vượt tồn hiện tại), chọn <b>Định khu Chờ Quyết Định</b> và ghi <b>Lý do cách ly</b>. Nút
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-biohazard mr-1"></i> Cách ly từ Tồn Kho</span> ở tab này dẫn thẳng sang đó.
        Khi còn chờ quyết định, bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span> để sửa số lượng, lý do, định khu.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/cach-ly-tu-ton-kho.png', 'caption' => 'Hộp thoại Cách ly chất chuẩn chờ quyết định (mở từ Tồn Kho)', 'width' => 560])
    </li>
    <li><b>Quyết định</b> <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-gavel"></i></span> (quyền <i>Quyết Định Loại Bỏ Chất Chuẩn Hỏng</i>),
        ghi căn cứ và nhập lại mật khẩu:
        <ul>
            <li><b>Loại bỏ</b>: ống chuyển sang định khu <b>Loại Bỏ/Chờ Huỷ</b>, không thể quay lại kho.</li>
            <li><b>Trả về kho</b>: chất chuẩn đạt, tồn được cộng lại, chọn vị trí đặt lại ống.</li>
        </ul>
    </li>
    <li><b>Huỷ</b>: khi đã tiêu huỷ thực tế, ở nhóm <b>Loại bỏ - Chờ huỷ</b> tick các phiếu, bấm
        <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-fire mr-1"></i> Huỷ mục đã chọn</span> (hoặc
        <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-fire"></i></span> trên từng phiếu), ghi phương pháp huỷ và nhập lại mật khẩu.
        Bước này không làm thay đổi tồn.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Phần cách ly hiện ở <a href="#so-su-dung">Sổ sử dụng</a> thành một dòng <b>Loại bỏ</b> kèm trạng thái phiếu cách ly, và tính vào cột
        <b>Huỷ trong kỳ</b> ở Tồn Kho. Dòng này chỉ điều chỉnh qua phiếu cách ly. Trả về kho thì số lượng dòng đó về 0, lịch sử vẫn giữ số cũ.</div>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phòng phải khai sẵn định khu chất chuẩn loại <b>Chờ Quyết Định</b> và <b>Loại Bỏ/Chờ Huỷ</b> ở Dữ Liệu Gốc › Định Khu. Chỉ cách ly
        một phần ống thì khi trả về kho chỉ đặt lại được đúng vị trí cũ của ống.</div>
</div>
