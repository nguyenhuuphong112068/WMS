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
        Trong khung chọn nhanh, bấm chọn <b>Đánh số tầng</b> trước: <i>Từ dưới lên</i> (Tầng 01 nằm dưới cùng) hoặc
        <i>Từ trên xuống</i> (Tầng 01 nằm trên cùng); số tầng hiện ngay cạnh từng hàng của khung, Tầng 01 tô đậm. Rồi rê
        chuột chọn số tầng × số ô ngang, nhập <b>Số cột</b>, <b>Số dãy sâu</b> và bấm vào ô để xác nhận. Tầng 01 mang mã
        <span class="ug-key">01</span> ở đúng vị trí đã chọn nên không cần đổi tên / mã từng tầng. Sau khi xác nhận, ô
        <b>Đánh số tầng</b> hiện ở góc phải đầu form để đổi lại nếu cần; lựa chọn áp cho mọi kệ tạo cùng lượt.</li>
    <li>Trên lưới, mỗi <b>cột</b> là một khối, mỗi <b>tầng</b> là một lưới nhỏ nhìn từ trên xuống, giống mặt kệ thật:
        chiều <b>Ngang</b> là các ô <span class="ug-key">A</span>, <span class="ug-key">B</span>, <span class="ug-key">C</span>...
        dọc mép trước tầng, chiều <b>Sâu</b> là các dãy <span class="ug-key">1</span>, <span class="ug-key">2</span>,
        <span class="ug-key">3</span>... từ cửa vào trong (dãy 1 sát cửa, vẽ dưới cùng). Mỗi ô là một vị trí.
        Nhập số ô ngang ở ô số <b>dưới thước chữ</b>, số dãy sâu ở ô số <b>bên phải dãy trong cùng</b>, hoặc <b>kéo góc</b>
        nhỏ ở góc dưới phải lưới: kéo ngang đổi số ô ngang, kéo lên/xuống đổi số dãy sâu. Tầng chỉ có một dãy thì để Sâu = 1. Bấm <b>Thêm cột</b> (ô nhỏ bên phải các cột) và <b>Thêm tầng</b>
        để mở rộng; ô <b>Mọi tầng</b> đặt cùng lúc Ngang × Sâu cho mọi tầng của cột (để trống ô nào thì giữ nguyên chiều
        đó), bấm <i class="fas fa-check"></i> để áp dụng. Góc phải tiêu đề cột có nút <i class="fas fa-clone"></i>
        <b>Nhân bản</b> (thêm cột mới cùng cấu trúc tầng) và <i class="fas fa-times"></i> <b>Gỡ cột</b>.</li>
    <li>Bấm vào <b>tên tầng</b> để mở hộp <b>Đổi tên / mã tầng</b>: <i>Tên tầng</i> là tên hiển thị, <i>Mã tầng</i> là đoạn
        mã của tầng nằm trong mã vị trí (mặc định là số thứ tự, ví dụ <span class="ug-key">06</span>; có thể đặt
        <span class="ug-key">T6</span> để mã vị trí thành <span class="ug-key">…/01/T6/A1</span>). Mã tầng 1-20 ký tự, chỉ gồm
        chữ không dấu, số và dấu <span class="ug-key">. _ -</span>, không trùng tầng khác trong cùng cột. Khi lưu, mã mọi vị trí
        của tầng đổi theo; nhãn đã in theo mã cũ cần in lại.</li>
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

<h3>Cách đọc mã vị trí</h3>
<p>Mã vị trí gồm 4 đoạn cách nhau bằng dấu <span class="ug-key">/</span>:
    <span class="ug-key">Mã kệ/tủ</span> / <span class="ug-key">Cột</span> / <span class="ug-key">Tầng</span> /
    <span class="ug-key">Ô ngang + Dãy sâu</span>. Ví dụ <span class="ug-key">QC2-INS-068/02/04/D4</span>:</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 18%">Đoạn mã</th>
                <th style="width: 14%">Cấp</th>
                <th>Ý nghĩa và ai đặt</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="ug-key">QC2-INS-068</span></td>
                <td>Kệ/Tủ</td>
                <td style="font-weight: 400">Mã kệ/tủ do người dùng nhập khi <b>Thêm kệ</b>. Hệ thống chỉ gợi ý
                    <span class="ug-key">&lt;mã kho&gt;/&lt;số kệ&gt;</span> (ví dụ <span class="ug-key">01/05</span>); đặt mã khác
                    (như mã thiết bị của tủ) thì mã vị trí không còn chứa mã kho.</td>
            </tr>
            <tr>
                <td><span class="ug-key">02</span></td>
                <td>Cột</td>
                <td style="font-weight: 400">Cột thứ 2 của kệ, hệ thống tự đánh số 01, 02, 03... Tên cột (ví dụ <i>Cánh phải</i>)
                    đổi được, mã cột giữ nguyên.</td>
            </tr>
            <tr>
                <td><span class="ug-key">04</span></td>
                <td>Tầng</td>
                <td style="font-weight: 400">Tầng thứ 4. Tầng 01 nằm dưới cùng hay trên cùng tuỳ kệ chọn đánh số <i>Từ dưới lên</i>
                    hay <i>Từ trên xuống</i>. Đổi được thành mã tự đặt ở hộp <b>Đổi tên / mã tầng</b> (ví dụ
                    <span class="ug-key">T4</span> → <span class="ug-key">…/02/T4/D4</span>).</td>
            </tr>
            <tr>
                <td><span class="ug-key">D</span></td>
                <td>Ô ngang</td>
                <td style="font-weight: 400">Ô thứ 4 dọc mép trước tầng, đếm từ trái sang: A = 1, B = 2, C = 3, D = 4. Hệ thống tự sinh.</td>
            </tr>
            <tr>
                <td><span class="ug-key">4</span></td>
                <td>Dãy sâu</td>
                <td style="font-weight: 400">Dãy thứ 4 tính từ cửa vào trong (dãy 1 sát cửa). Hệ thống tự sinh.</td>
            </tr>
        </tbody>
    </table>
</div>
<p>Đọc liền: tủ <b>QC2-INS-068</b> → cột <b>02</b> → tầng <b>04</b> → ô ngang thứ 4 (<b>D</b>), dãy thứ <b>4</b> tính từ cửa vào.
    Ô chọn Định khu ở hộp thoại <b>Khai Hoá Chất Cho Phòng</b> chỉ hiện mã này; các màn khác hiện kèm tên
    <i>Kho / Kệ / Cột / Tầng</i> phía trước.</p>

<h3>Gán phân loại và loại lưu trữ cho vị trí</h3>
<ol class="ug-steps">
    <li>Mở một kệ đã lưu. <b>Bấm</b> hoặc <b>rê chuột</b> qua các ô để chọn nhiều vị trí; giữ <b>Shift</b> rồi kéo chuột để quét một khung chữ nhật, mọi ô chạm khung (kể cả ở nhiều tầng, nhiều cột) được thêm vào lựa chọn; bấm <b>Chọn cả kệ</b> để chọn tất cả.</li>
    <li>Chọn <b>Phân loại</b>: <i>Dự Phòng</i>, <i>Sử Dụng</i>, <i>Biệt Trữ</i>, <i>Cách Ly Chờ Quyết Định</i>, <i>Loại Bỏ/Chờ Huỷ</i>.
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
