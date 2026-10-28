<p>Nhật ký mọi lần hoá chất rời kho của phòng: <b>Sử dụng</b>, <b>Loại bỏ</b> (sinh từ phiếu cách ly) và chuyển cho phòng khác.
    Mỗi dòng ghi mã xuất nhập, hoá chất, số lượng, ngày sử dụng, người sử dụng, người kiểm tra và mục đích.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/so-su-dung.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Hoá Chất › tab Sổ sử dụng hoá chất'])

<h3>Sửa một dòng</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phiếu Nhập', true, 'Đổi sang lô khác thì mã xuất nhập đổi theo.'],
    ['Số Lượng', true, 'Vẫn áp dụng hạn mức vượt tồn tối đa 5%.'],
    ['Ngày Sử Dụng', false, 'Ghi lúc lập phiếu, không sửa được.'],
    ['Người Kiểm Tra', false, 'Bắt buộc với hoá chất cấm.'],
    ['Số PKN, OOS, BCSL...', false, 'Số phiếu kiểm nghiệm, báo cáo OOS, báo cáo sai lệch làm căn cứ.'],
    ['Mục Đích Sử Dụng', false, ''],
    ['Lý Do Điều Chỉnh', false, 'Không lưu trên phiếu, chỉ ghi vào lịch sử để giải thích lần sửa.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phiếu chỉ <b>khoá</b> chứ không xoá; phiếu đã khoá không trừ tồn. Dòng sinh từ phiếu cách ly chỉ xử lý ở tab
        <a href="#cach-ly">Cách ly</a>; dòng chuyển cho phòng khác không sửa được (muốn thu hồi thì phòng nhận Từ chối nhận).</div>
</div>
