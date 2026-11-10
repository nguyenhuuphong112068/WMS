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
    <li>Trong kho, bấm <b>Kệ/Tủ mới</b>, nhập <b>Mã kệ/tủ</b> và <b>Tên kệ/tủ</b>. Hệ thống báo ngay nếu mã kệ đã tồn tại.</li>
    <li>Khai cả kệ ở khung <b>Thông số kệ/tủ</b>: bốn thẻ cùng một kiểu, mỗi thẻ có hình minh hoạ, ô số và hai nút
        <span class="ug-key">−</span> / <span class="ug-key">+</span>:
        <ol class="mb-1">
            <li><b>Số cột</b> của một kệ/tủ (Cột 01, 02, 03... từ trái sang).</li>
            <li><b>Số tầng</b> của mỗi cột. Nút dưới ô số đổi chiều đánh số: <i>Tầng 01 dưới cùng</i> hoặc
                <i>Tầng 01 trên cùng</i>; lựa chọn áp cho mọi kệ tạo cùng lượt.</li>
            <li><b>Vị trí ngang</b> mỗi tầng: các ô <span class="ug-key">A</span>, <span class="ug-key">B</span>,
                <span class="ug-key">C</span>... dọc mép trước tầng.</li>
            <li><b>Vị trí sâu</b> mỗi tầng: các dãy <span class="ug-key">1</span>, <span class="ug-key">2</span>... từ cửa vào
                trong (dãy 1 sát cửa). Tầng chỉ có một dãy thì để 1.</li>
        </ol>
        Gõ số rồi bấm <span class="ug-key">Enter</span>, bấm <span class="ug-key">−</span> / <span class="ug-key">+</span>, hoặc
        dùng phím mũi tên lên/xuống trong ô số. Đổi số ở thẻ nào thì chỉ chiều đó đổi cho cả kệ, các chiều khác giữ nguyên.
        Dòng phía trên các thẻ tính sẵn tổng, ví dụ <i>3 cột × 4 tầng × 5 ngang × 2 sâu = 120 vị trí</i>. Kệ mới mở ra đã
        điền sẵn thông số của lần tạo kệ trước.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/dinh-khu-thong-so.png', 'caption' => 'Khung Thông số kệ/tủ và mô hình 3D khi tạo kệ mới'])
    </li>
    <li>Bên phải khung là <b>mô hình 3D</b> của kệ, vẽ lại ngay khi đổi số: vách giữa các cột, mặt từng tầng (nẹp đậm là
        Tầng 01), các ô vị trí trên mặt tầng (mép trước là dãy 1; tầng có ít vị trí hơn tầng rộng nhất thì các ô tự chia đều trên cả
        mặt tầng). Trên nóc mỗi cột có tên cột kèm <b>mã cột</b> (ví dụ <span class="ug-key">EN1/03/01</span>), trên mặt mỗi ô in
        <b>mã vị trí</b> (đoạn cuối của mã, ví dụ <span class="ug-key">A1</span>, <span class="ug-key">B2</span>); rê chuột vào ô để xem
        mã đầy đủ. <b>Kéo chuột</b> trên mô hình để xoay, nhấp đúp để về góc
        nghiêng; các nút <b>Nghiêng</b>, <b>Chính diện</b>, <b>Từ trên</b> đổi góc nhìn, <i class="fas fa-search-minus"></i>
        / <i class="fas fa-search-plus"></i> thu nhỏ / phóng to. Rê chuột vào thẻ thông số nào thì phần tương ứng sáng lên
        trên mô hình (ví dụ thẻ Vị trí ngang tô các ô ở mép trước). Khi sửa kệ đã có, ô trên mô hình tô màu như lưới bên
        dưới (theo phân loại hoặc trạng thái tuỳ nút <b>Tô màu theo</b>). Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột (khung thường: <span class="ug-key">Shift</span> + lăn chuột) để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.</li>
    <li>Kệ có chỗ khác thường (một cột ít tầng hơn, một tầng rộng hơn...) thì chỉnh riêng ở lưới bên dưới. Khi đó thẻ của
        chiều bị lệch viền nét đứt màu cam, ô số hiện <span class="ug-key">≠</span> (<i>Đang khác nhau giữa các tầng</i>);
        nhập một số vào thẻ đó sẽ đặt lại cùng một giá trị cho cả kệ.</li>
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

<h3>Mặt bằng kho/phòng: hình dạng phòng, cửa và vị trí các kệ/tủ</h3>
<p>Sau khi đã xây các kệ/tủ, khai luôn hình dạng phòng và kệ đứng ở đâu để mọi người hình dung được kho. Phòng không cần
    là hình chữ nhật: vẽ được <b>đa giác</b> bất kỳ (phòng chữ L, góc vát, bậc thang...). Mọi số đo tính bằng <b>mét</b>; sơ đồ nhìn
    từ trên xuống, mép trên của sơ đồ là phía trong cùng khi xem 3D.</p>
<ol class="ug-steps">
    <li>Mở một kho/phòng (danh sách kệ/tủ), bấm <span class="btn btn-sm btn-outline-primary ug-ui"><i class="fas fa-vector-square mr-1"></i> Mặt bằng kho/phòng</span>.</li>
    <li>Khai <b>Chiều ngang phòng</b> × <b>Chiều sâu phòng</b> (nút <span class="ug-key">−</span> / <span class="ug-key">+</span>
        mỗi lần 0,5 m, hoặc gõ số) - phòng đa giác thì hai ô này là khung bao, đổi số thì cả hình co giãn theo.</li>
    <li><b>Vẽ hình dạng phòng</b> trên sơ đồ: kéo <b>chấm tròn</b> ở mỗi góc để dời góc (bước 0,05 m; kéo gần thẳng hàng với góc
        bên cạnh thì tự hút cho tường thẳng); bấm dấu <b>+</b> ở giữa một cạnh để thêm góc rồi kéo đi; <b>nhấp đúp</b> vào một góc để
        xoá góc (tối thiểu 3 góc). Nút <b>Hình chữ nhật</b> đưa phòng về hình chữ nhật theo khung bao. Các cạnh không được cắt nhau
        (viền phòng chuyển đỏ khi bị cắt).</li>
    <li><b>Cửa ra vào</b>: bấm <b>Thêm cửa</b> (cửa rộng 1 m đặt giữa bức tường dài nhất), rồi <b>kéo cửa dọc theo tường</b> - kéo
        sang tường khác được; kéo <b>ô vuông ở hai đầu cửa</b> hoặc chỉnh ô <b>Rộng cửa</b> để đổi bề rộng (0,5 - 4 m). <b>Bỏ cửa</b>
        nếu phòng không cần vẽ cửa.</li>
    <li>Đặt kệ vào phòng: kéo tên kệ ở dòng <b>Kệ/tủ chưa đặt</b> thả vào sơ đồ, bấm vào tên kệ để đặt vào chỗ trống đầu tiên,
        hoặc bấm <b>Tự xếp kệ chưa đặt</b> để xếp lần lượt tất cả. Kệ mới đặt lấy kích thước gợi ý theo số ô của kệ.</li>
    <li>Kéo kệ trên sơ đồ để dời chỗ (bước 0,05 m, không ra được ngoài tường). Bấm chọn một kệ để chỉnh ở khung bên phải:
        <b>Bề ngang kệ (mặt trước)</b>, <b>Bề sâu kệ</b>, <b>Cách mép trái</b>, <b>Cách mép trên</b> (tính từ mép khung bao) và
        <b>Mặt trước (phía lấy hàng) hướng</b> xuống / trái / lên / phải. Trên sơ đồ, mặt trước là cạnh đậm có mũi tên.
        Phím tắt khi đang chọn kệ: mũi tên dời 0,1 m (giữ <span class="ug-key">Shift</span>: 0,5 m),
        <span class="ug-key">R</span> xoay 90°, <span class="ug-key">Delete</span> gỡ khỏi mặt bằng,
        <span class="ug-key">Esc</span> bỏ chọn.</li>
    <li>Khung 3D bên dưới vẽ cả phòng: sàn đúng hình dạng phòng kẻ ô 1 m, tường, cửa, các kệ đúng chỗ, đúng hướng, mỗi tầng cao 0,4 m; vị trí
        đang có lô hàng hiện thành thùng hàng. Kéo chuột để xoay; bấm một kệ trên 3D để chọn, nhấp đúp (trên sơ đồ hoặc 3D) hay
        bấm <b>Mở cấu trúc kệ</b> để sang sửa cấu trúc kệ đó.
        Nút <b><i class="fas fa-walking"></i> Xuyên tủ</b> cho đi bộ trong phòng như game 3D: <span class="ug-key">W</span> <span class="ug-key">A</span> <span class="ug-key">S</span> <span class="ug-key">D</span> đi, <span class="ug-key">Shift</span> chạy, bấm vào khung rồi rê chuột để nhìn quanh (<span class="ug-key">Esc</span> thả chuột), đi xuyên qua các tủ để thấy tủ bên trong; không phóng to, nút 80° / 100° / 120° chọn độ rộng góc nhìn (góc rộng thấy được nhiều hơn).
        Nút <i class="fas fa-expand"></i> ở góc phải khung 3D <b>mở to toàn màn hình</b>: lăn chuột (khung thường: <span class="ug-key">Shift</span> + lăn chuột) để phóng to / thu nhỏ, <span class="ug-key">Shift</span> + kéo (hoặc kéo chuột phải) để dời khung nhìn, <span class="ug-key">Esc</span> hoặc nút <i class="fas fa-compress"></i> để thu về.
        @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/materData/dinh-khu-mat-bang.png', 'caption' => 'Mặt bằng kho/phòng: phòng đa giác (chấm tròn ở góc, dấu + giữa cạnh), cửa kéo thả trên tường, kệ đang chọn và 3D cả phòng'])
    </li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-save mr-1"></i> Lưu mặt bằng</span>. <b>Hoàn tác</b> bỏ các
        thay đổi chưa lưu.</li>
</ol>
<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Không lưu được khi còn kệ <b>chồng lên nhau</b> hoặc <b>nằm ra ngoài phòng</b> (kệ đó tô đỏ, dòng trạng thái ghi số kệ
        lỗi), khi các cạnh phòng cắt nhau, hay khi cửa vượt ra ngoài tường. Thu nhỏ phòng hoặc kéo góc vào trong có thể làm kệ
        đang đặt lọt ra ngoài: hãy dời kệ trước.
        Mặt bằng chỉ là vị trí đặt kệ, không đổi mã hay cấu trúc cột / tầng / vị trí.</div>
</div>

<h3>Chế độ "Danh sách định khu"</h3>
<p>Mỗi cấp là một tab với đủ các nút Thêm, Sửa, Khoá/Mở khoá và <b>Xoá</b>. Dùng khi cần sửa riêng một bản ghi hoặc tra cứu nhanh.</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Không thu nhỏ (cả chiều Ngang lẫn Sâu) hoặc gỡ được vị trí <b>đang có lô hàng</b>. Hãy chuyển hàng đi trước.</li>
            <li>Thu nhỏ lưới thì vị trí thừa bị <b>khoá</b> chứ không xoá; nới lại sẽ mở đúng các vị trí đó.</li>
            <li>Bớt <b>Số cột</b> / <b>Số tầng</b> ở khung Thông số sẽ gỡ cột bên phải nhất / tầng có số lớn nhất (cột, tầng
                đã lưu thì bị khoá); thêm lại thì mở lại cột, tầng đang khoá trước rồi mới tạo mới.</li>
            <li>Chỉ xoá được một cấp khi cấp đó <b>chưa có cấp con</b> (ví dụ kệ không còn cột nào).</li>
        </ul>
    </div>
</div>
