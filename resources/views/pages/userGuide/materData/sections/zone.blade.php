<p>Khai báo cấu trúc lưu trữ của phòng ban theo 5 cấp:
    <span class="ug-key">Kho/Phòng</span> → <span class="ug-key">Kệ/Tủ</span> → <span class="ug-key">Cột</span> →
    <span class="ug-key">Tầng</span> → <span class="ug-key">Vị Trí</span>.
    Khi nhập hàng, mỗi lô được đặt vào một <b>vị trí</b>. Nhãn in và màn hình Tồn đều hiện theo mã vị trí.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/dinh-khu.png', 'caption' => 'Màn hình Định Khu'])

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Định Khu làm việc trên <b>phòng ban đang chọn</b> ở thanh trên cùng. Màn hình có hai chế độ:
        <b>Cấu trúc kho</b> (khai nhanh trên lưới, nên dùng) và <b>Danh sách định khu</b> (bảng từng cấp).</div>
</div>

<h3>Chế độ "Cấu trúc kho": khai trên lưới</h3>
<ol class="ug-steps">
    <li>Chọn thẻ <b>kho/phòng</b> có sẵn hoặc bấm nút thêm kho để tạo mới (nhập <b>Mã</b>, ví dụ <span class="ug-key">03</span>,
        và <b>Tên</b>, ví dụ <i>Kho 03</i>), rồi bấm <b>Lưu kho/phòng</b>.</li>
    <li>Trong kho, bấm <b>Thêm kệ</b>, nhập <b>Mã kệ/tủ</b> và <b>Tên kệ/tủ</b>. Hệ thống báo ngay nếu mã kệ đã tồn tại.
        Chọn <b>Đánh số tầng</b>: <i>Từ dưới lên</i> (Tầng 01 nằm dưới cùng, mặc định) hoặc <i>Từ trên xuống</i>
        (Tầng 01 nằm trên cùng). Lựa chọn này áp cho mọi kệ tạo cùng lượt và chỉ đổi cách xếp tầng trên lưới, mã tầng không đổi.</li>
    <li>Trên lưới, mỗi <b>cột</b> là một khối, mỗi <b>tầng</b> là một lưới nhỏ nhìn từ trên xuống, giống mặt kệ thật:
        chiều <b>Ngang</b> là các ô <span class="ug-key">A</span>, <span class="ug-key">B</span>, <span class="ug-key">C</span>...
        dọc mép trước tầng, chiều <b>Sâu</b> là các dãy <span class="ug-key">1</span>, <span class="ug-key">2</span>,
        <span class="ug-key">3</span>... từ cửa vào trong (dãy 1 sát cửa, vẽ dưới cùng). Mỗi ô là một vị trí.
        Nhập số ô ngang ở ô số <b>dưới thước chữ</b>, số dãy sâu ở ô số <b>bên phải dãy trong cùng</b>, hoặc <b>kéo góc</b>
        nhỏ ở góc dưới phải lưới: kéo ngang đổi số ô ngang, kéo lên/xuống đổi số dãy sâu. Tầng chỉ có một dãy thì để Sâu = 1. Bấm <b>Thêm cột</b> (ô nhỏ bên phải các cột) và <b>Thêm tầng</b>
        để mở rộng; ô <b>Mọi tầng</b> đặt cùng lúc Ngang × Sâu cho mọi tầng của cột (để trống ô nào thì giữ nguyên chiều
        đó), bấm <i class="fas fa-check"></i> để áp dụng. Góc phải tiêu đề cột có nút <i class="fas fa-clone"></i>
        <b>Nhân bản</b> (thêm cột mới cùng cấu trúc tầng) và <i class="fas fa-times"></i> <b>Gỡ cột</b>.</li>
    <li>Bấm vào tên cột để đổi tên nếu cần. Xem phần chú thích màu phía trên lưới để biết ô nào <i>Sẽ tạo</i> (vị
        trí mới) và ô nào <i>Sẽ khoá</i> (vị trí sẽ bị thu lại) trước khi lưu.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu cấu trúc</span>. Muốn bỏ các thay
        đổi chưa lưu thì bấm <b>Hoàn tác</b>.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div><b>Mã tự sinh theo cấp cha</b>: kệ <span class="ug-key">01/01</span> → cột <span class="ug-key">01/01/01</span> →
        tầng <span class="ug-key">01/01/01/03</span> → vị trí <span class="ug-key">01/01/01/03/E2</span> (ô E, dãy 2 của tầng 03).
        Chữ ô ngang tầng nào cũng bắt đầu lại từ A; quá 26 ô thì tiếp AA, AB... Đổi mã kệ thì mã của
        mọi cột, tầng, vị trí con đổi theo.</div>
</div>

<h3>Gán phân loại và loại lưu trữ cho vị trí</h3>
<ol class="ug-steps">
    <li>Mở một kệ đã lưu. <b>Bấm</b> hoặc <b>rê chuột</b> qua các ô để chọn nhiều vị trí; bấm <b>Chọn cả kệ</b> để chọn tất cả.</li>
    <li>Chọn <b>Phân loại</b>: <i>Dự Phòng</i>, <i>Sử Dụng</i>, <i>Biệt Trữ</i>, <i>Chờ Quyết Định</i>, <i>Loại Bỏ/Chờ Huỷ</i>.
        Mỗi phân loại có một màu riêng trên lưới.</li>
    <li>Chọn <b>Loại lưu trữ</b>: <i>Vật tư</i>, <i>Hoá chất</i>, <i>Chất chuẩn</i> hoặc <i>Dùng chung</i>. Màn hình Tồn của
        từng loại chỉ hiện các vị trí đúng loại; vị trí <i>Dùng chung</i> hiện ở cả ba màn hình.</li>
    <li>Bấm <b>Áp dụng</b>. Chọn <i>- Giữ nguyên -</i> ở ô nào thì ô đó không bị thay đổi.</li>
</ol>
<p>Nút <b>Tô màu theo</b> chuyển cách tô lưới: theo <b>Phân loại</b> hoặc theo <b>Trạng thái</b> (có lô hàng, trống, đang khoá).</p>

<h3>Chế độ "Danh sách định khu"</h3>
<p>Mỗi cấp là một tab với đủ các nút Thêm, Sửa, Khoá/Mở khoá và <b>Xoá</b>. Dùng khi cần sửa riêng một bản ghi hoặc tra cứu nhanh.</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Không thu nhỏ (cả chiều Ngang lẫn Sâu) hoặc gỡ được vị trí <b>đang có lô hàng</b>. Hãy chuyển hàng đi trước.</li>
            <li>Thu nhỏ lưới thì vị trí thừa bị <b>khoá</b> chứ không xoá; nới lại sẽ mở đúng các vị trí đó.</li>
            <li>Chỉ xoá được một cấp khi cấp đó <b>chưa có cấp con</b> (ví dụ kệ không còn cột nào).</li>
        </ul>
    </div>
</div>
