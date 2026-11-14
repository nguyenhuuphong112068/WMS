<p>Nhật ký mọi lần hoá chất rời kho của phòng: <b>Sử dụng</b>, <b>Loại bỏ</b> (sinh từ phiếu cách ly), chuyển cho phòng khác và
    <b>Trả về kho</b> (Acetonitril / Methanol trả lại, số lượng âm). Mỗi dòng ghi mã xuất nhập, hoá chất, số lượng, ngày sử dụng,
    người sử dụng, người kiểm tra và mục đích.</p>
<ul>
    <li>Dòng Sử dụng đã có lượng trả hiện thêm <i>↩ Đã trả X · Thực dùng Y</i>; dòng Trả về kho hiện <i>của phiếu SD &lt;ngày&gt; · &lt;SL&gt;</i>.
        Bấm vào dòng chữ đó để lọc sổ theo mã xuất nhập (mọi ngày). Xem <a href="#tra-ve-kho">Trả Acetonitril / Methanol về kho</a>.</li>
</ul>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/so-su-dung.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Hoá Chất › tab Sổ sử dụng hoá chất'])

<h3>Sửa một dòng</h3>
<p>Người có quyền <b>Điều Chỉnh Sổ Sử Dụng Hoá Chất</b> bấm <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>
    để sửa hoặc <span class="btn btn-sm btn-secondary ug-ui"><i class="fas fa-lock"></i></span> để khoá / mở khoá một dòng ghi sai.
    Phiếu của <b>ngày cũ</b> vẫn sửa được. Mọi lần điều chỉnh đều lưu lịch sử - số nhỏ ở góc nút Sửa
    là số lần đã điều chỉnh, bấm vào để mở bảng <b>Lịch Sử Điều Chỉnh</b>: mỗi lần thay đổi là một dòng gồm người - thời điểm thay đổi, cột <b>Lý Do Điều Chỉnh</b> và các cột giống bảng sổ; ô đã đổi so với lần trước được tô vàng.</p>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phiếu Nhập', true, 'Đổi sang lô khác thì mã xuất nhập đổi theo (phiếu đã có lượng trả về kho thì không đổi lô được).'],
    ['Số Lượng', true, 'Vẫn áp dụng hạn mức vượt tồn tối đa 5%. Phiếu đã có lượng trả về kho thì không nhỏ hơn lượng đã trả.'],
    ['Ngày Sử Dụng', false, 'Ghi lúc lập phiếu, không sửa được.'],
    ['Người Kiểm Tra', false, 'Bắt buộc với hoá chất cấm.'],
    ['Số PKN, OOS, BCSL...', false, 'Số phiếu kiểm nghiệm, báo cáo OOS, báo cáo sai lệch làm căn cứ.'],
    ['Mục Đích Sử Dụng', false, ''],
    ['Lý Do Điều Chỉnh', false, 'Không lưu trên phiếu, chỉ ghi vào lịch sử để giải thích lần sửa.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phiếu chỉ <b>khoá</b> chứ không xoá; phiếu đã khoá không trừ tồn. Dòng sinh từ phiếu cách ly chỉ xử lý ở tab
        <a href="#cach-ly">Cách ly</a>; dòng chuyển cho phòng khác không sửa được (muốn thu hồi thì phòng nhận Từ chối nhận); dòng Trả về kho chỉ huỷ ở tab
        <a href="#tra-ve-kho">Trả về kho</a>, và phiếu sử dụng đã có lượng trả thì phải huỷ phiếu trả trước khi khoá.</div>
</div>
