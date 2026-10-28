<p>Ma trận cho biết ngay việc đang dồn ở đâu. <b>Hàng</b> là nhóm nghiệp vụ, <b>cột</b> là đối tượng, mỗi ô là số đề nghị đang
    chờ. Cột và hàng <b>Tổng</b> cộng dồn.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/general/home-matrix.png', 'caption' => 'Ma trận tổng quan của Trưởng Bộ Phận QC1: 23 mục Phê duyệt dữ liệu chờ ký'])

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 220px">Hàng (nghiệp vụ)</th>
                <th>Gồm</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><i class="fas fa-file-invoice"></i> Dự trù</td><td style="font-weight: 400">Ký dự trù, dự trù mới chờ tiếp nhận, đề nghị huỷ mục dự trù.</td></tr>
            <tr><td><i class="fas fa-clipboard-list"></i> Đề nghị nội bộ</td><td style="font-weight: 400">Ký đề nghị cấp phát, phiếu chờ kho cấp, chuẩn đã cấp cho bạn.</td></tr>
            <tr><td><i class="fas fa-exchange-alt"></i> Liên phòng ban</td><td style="font-weight: 400">Ký đề nghị liên phòng ban, chờ phòng bạn cấp phát, hàng đã cấp chờ bạn nhận.</td></tr>
            <tr><td><i class="fas fa-paperclip"></i> Văn phòng phẩm</td><td style="font-weight: 400">Ký đề nghị VPP, chờ phòng bạn cấp VPP, VPP đã cấp chờ bạn nhận.</td></tr>
            <tr><td><i class="fas fa-shield-alt"></i> KS chất lượng / hàng hỏng</td><td style="font-weight: 400">Vật tư chờ QA hiệu chuẩn; vật tư, hoá chất, chất chuẩn hỏng chờ quyết định.</td></tr>
            <tr><td><i class="fas fa-check-double"></i> Phê duyệt dữ liệu</td><td style="font-weight: 400">Danh mục chờ duyệt, dữ liệu gốc chờ duyệt.</td></tr>
        </tbody>
    </table>
</div>

<p>Cột đối tượng có màu cố định, dùng lại cho các thẻ bên dưới: <b>Vật tư</b>, <b>Hoá chất</b>, <b>Chất chuẩn</b> và
    <b>Dùng chung</b> (dữ liệu không thuộc riêng loại hàng nào như đơn vị tính, nhà sản xuất).</p>

<h3>Ô tách hai chiều</h3>
<p>Ở hàng <b>Liên phòng ban</b> và <b>Văn phòng phẩm</b>, ô tách thành <b>←</b> số đề nghị phòng khác gửi tới phòng bạn và
    <b>→</b> số đề nghị phòng bạn gửi đi.</p>

<h3>Bấm vào ô</h3>
<p>Bấm một ô có số (hoặc chọn ô bằng phím <span class="ug-key">Tab</span> rồi <span class="ug-key">Enter</span>): trang cuộn tới
    nhóm thẻ của nghiệp vụ đó và nháy sáng các thẻ đúng đối tượng. Bấm ô ở cột Tổng thì nháy mọi thẻ của nhóm.</p>
