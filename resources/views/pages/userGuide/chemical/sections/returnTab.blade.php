<p>Dung môi pha động <b>Acetonitril</b> và <b>Methanol</b> lấy ra dùng không hết được khai <b>trả lại đúng lô đã lấy</b>. Lượng trả
    được cộng lại vào tồn của lô đó, Sổ sử dụng ghi thêm một dòng <b>Trả về kho</b> (số âm) nên lượng "đã sử dụng" luôn là lượng thực dùng.
    Chỉ hai hoá chất này (theo tên trong danh mục) mới trả về kho được.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/chemical/tra-ve-kho.png', 'caption' => 'Màn hình Đề Nghị - Sử Dụng › Hoá Chất › tab Trả hoá chất Acetonitril hoặc Methanol về kho'])

<h3>Hai nhóm xem</h3>
<ul>
    <li><b><i class="fas fa-flask"></i> Phiếu sử dụng</b>: các phiếu sử dụng Acetonitril / Methanol còn hiệu lực, cột <b>Đã Sử Dụng</b>,
        <b>Đã Trả</b>, <b>Còn Trả Được</b> và <b>Lý Do Trả Về</b> (thay cho Mục đích sử dụng).</li>
    <li><b><i class="fas fa-history"></i> Lịch sử trả về kho</b>: từng lần trả (mã xuất nhập, lượng trả, ngày, người trả, lý do, phiếu
        sử dụng gốc, trạng thái).</li>
</ul>

<h3>Trả về kho</h3>
<ol class="ug-steps">
    <li>Ở nhóm Phiếu sử dụng, bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-undo-alt"></i></span> ở dòng phiếu cần trả.</li>
    <li>Hộp <b>Trả hoá chất về kho</b>: nhập <b>Lượng trả về kho</b> và <b>Lý do trả về</b> (bắt buộc, tối đa 500 ký tự), bấm
        <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-undo-alt mr-1"></i> Trả về kho</span>.</li>
    <li>Tồn của lô tăng ngay, Sổ sử dụng có thêm dòng <b>Trả về kho</b>.</li>
</ol>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Lượng trả về kho', true, 'Lớn hơn 0. Tổng các lần trả của một phiếu không vượt số lượng của phiếu đó (cột Còn Trả Được).'],
    ['Lý do trả về', true, 'Ví dụ: pha động dùng không hết. Thiếu thì báo <i>"Vui lòng nhập lý do trả về."</i>'],
]])

<h3>Huỷ phiếu trả</h3>
<p>Khai nhầm thì ở nhóm Lịch sử trả về kho bấm <span class="btn btn-sm btn-outline-danger ug-ui"><i class="fas fa-undo"></i></span>
    <b>Huỷ phiếu trả về kho</b>, ghi lý do. Lượng đã trả bị trừ lại khỏi tồn của lô; lô không còn đủ tồn thì không huỷ được.</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phiếu sử dụng đã có lượng trả về kho thì ở Sổ sử dụng <b>không khoá</b> và <b>không đổi sang lô khác</b> được, và không sửa số lượng
        nhỏ hơn lượng đã trả; muốn làm thì huỷ phiếu trả trước. Dòng <b>Trả về kho</b> trên Sổ sử dụng không sửa / khoá được, chỉ huỷ ở tab này.
        Trả về kho và huỷ phiếu trả cần quyền <b>Trả Hoá Chất Acetonitril / Methanol Về Kho</b>; xem tab cần quyền
        <b>Tab Trả hoá chất Acetonitril hoặc Methanol về kho</b>.</div>
</div>
