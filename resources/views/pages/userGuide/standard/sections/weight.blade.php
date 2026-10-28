<p>Với ống tick <b>Chuẩn có cần kiểm soát khối lượng</b>, hệ thống so lượng chuẩn thực có trong ống với tổng lượng đã ghi sử dụng,
    để phát hiện hao hụt bất thường. Ô này chỉ tick được khi ĐVT của phòng là <b>mg</b>, hoặc <b>ml</b> đã khai tỉ trọng d
    ở Danh Mục Chất Chuẩn (để quy số cân mg ra ml).</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/standard/khoi-luong.png', 'caption' => 'Màn hình Tồn Kho › Chất Chuẩn › tab Kiểm soát khối lượng'])

<ol class="ug-steps">
    <li>Lần dùng đầu tiên, nhập <b>Khối lượng Bì + Chuẩn trước khi dùng</b> trên phiếu sử dụng. Số cân luôn là khối lượng:
        chuẩn có ĐVT khối lượng (mg, g) cân theo đúng ĐVT đó, chuẩn có ĐVT thể tích (ml) cân theo <b>mg</b>.</li>
    <li>Khi ống đã dùng xong, ở tab <b>Kiểm soát khối lượng</b> nhập <b>Khối lượng bì sau khi sử dụng</b> (không được lớn hơn Bì + Chuẩn).</li>
    <li>Hệ thống tính <b>KL thực</b> = Bì + Chuẩn − Bì sau khi sử dụng (chuẩn ĐVT ml: KL thực = chênh lệch (mg) / 1000 / tỷ trọng d,
        d khai ở Danh Mục Chất Chuẩn), rồi <b>sai lệch (%)</b> = |KL thực − tổng lượng đã ghi sử dụng| / tổng lượng đã ghi sử dụng × 100,
        so với giới hạn của dạng chuẩn ở bảng dưới.</li>
    <li>Chưa đủ số liệu thì cột Kết quả ghi rõ lý do thay vì tính: <i>Chưa sử dụng</i>, <i>Chưa cân Bì + Chuẩn</i>,
        <i>Đang sử dụng - chưa cân bì sau dùng</i> (ống đang dùng dở), <i>Thiếu tỉ trọng d</i> (chuẩn ĐVT ml chưa khai tỷ trọng).</li>
    <li>Ống vượt giới hạn: ghi <b>Nhận xét ngoài giới hạn cho phép</b> (quyền <i>Nhận xét chuẩn ngoài giới hạn</i>).</li>
</ol>

<h3>Giới hạn sai lệch cho phép (%)</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th>Dạng chuẩn</th>
                {{-- Giữ chữ thường để đơn vị "mg" không bị CSS bảng đổi thành "MG" --}}
                <th class="text-center" style="text-transform: none">Quy cách &lt; 10 mg</th>
                <th class="text-center" style="text-transform: none">10 - 100 mg</th>
                <th class="text-center" style="text-transform: none">&gt; 100 mg</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dạng Bột Rời</td>
                <td class="text-center" style="font-weight: 400">50%</td>
                <td class="text-center" style="font-weight: 400">60%</td>
                <td class="text-center" style="font-weight: 400">70%</td>
            </tr>
            <tr>
                <td>Dạng Bột Mịn</td>
                <td class="text-center" style="font-weight: 400">20%</td>
                <td class="text-center" style="font-weight: 400">30%</td>
                <td class="text-center" style="font-weight: 400">50%</td>
            </tr>
            <tr>
                <td>Dạng Sệt</td>
                <td class="text-center" style="font-weight: 400">10%</td>
                <td class="text-center" style="font-weight: 400">20%</td>
                <td class="text-center" style="font-weight: 400">50%</td>
            </tr>
        </tbody>
    </table>
</div>
<p class="mb-0 mt-2">Quy cách là lượng chuẩn của ống lúc nhập, quy ra mg (đơn vị g hoặc ml được nhân 1000).</p>
