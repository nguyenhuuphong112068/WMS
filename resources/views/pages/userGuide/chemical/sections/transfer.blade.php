<p>Phòng <b>A</b> (đang thiếu) xin hoá chất từ kho của phòng <b>B</b>. Hàng chuyển từ tồn của B sang tồn của A.
    Đề nghị hoá chất liên phòng ban <b>không có bước ký duyệt</b>.</p>

<ol class="ug-steps">
    <li><b>A lập đề nghị</b>: bấm <span class="btn btn-sm btn-primary ug-ui"><i class="fas fa-plus mr-1"></i> Tạo đề nghị chuyển liên phòng ban</span>,
        chọn phòng được đề nghị, thêm hoá chất, số lượng, ngày mong muốn rồi gửi. Đề nghị còn ở trạng thái lưu thì vẫn sửa
        hoặc huỷ được.</li>
    <li><b>B cấp phát</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-hand-holding-medical mr-1"></i> Cấp phát</span>,
        chọn lô trong kho của B (gợi ý FEFO/FIFO) và số lượng, hoặc <b>Từ chối cấp phát</b> từng mục. Cấp phát trừ tồn của B ngay,
        mục chuyển sang <i>chờ nhận</i>.</li>
    <li><b>A nhận hàng</b>: bấm <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-inbox mr-1"></i> Nhận</span>. Bước nhận
        cũng là bước kiểm tra: ghi kết luận, chọn <b>định khu thật</b> của phòng A. Lô mới vào tồn của A ngay với mã gốc kèm hậu tố
        <span class="ug-key">-CK1</span>, <span class="ug-key">-CK2</span>... Hàng không đạt thì <b>Từ chối nhận</b>, tồn tự hoàn lại cho B.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>A phải khai hoá chất ở <a href="#hoa-chat-cua-phong">Hoá Chất Của Phòng</a> trước khi bấm Nhận.</li>
            <li>Lô <b>chưa xác định hạn dùng nội bộ</b> chỉ được chuyển <b>nguyên cả lô</b> (lô chưa mở, phòng nhận tự xác định);
                muốn cấp lẻ thì B phải xác định hạn nội bộ trước.</li>
            <li>Lô nhận lẻ ở phòng A không được dùng vượt tồn, vì số lượng đã được phòng gửi cân chia sẵn.</li>
        </ul>
    </div>
</div>
