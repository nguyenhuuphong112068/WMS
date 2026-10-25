<h3>Mã ống chuẩn</h3>
<p>Mỗi ống được cấp một mã tự sinh: <b>tên viết tắt phòng + mã nhóm chuẩn + năm + tháng + số thứ tự 4 chữ số</b>, ví dụ
    <span class="ug-key">QC1VKN26010036</span>. Mã được in trên nhãn dán ống; mọi thao tác sau đều chỉ định đúng mã này.</p>

<h3>Nhóm chuẩn</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 90px" class="text-center">STT</th>
                <th style="width: 120px">Viết tắt</th>
                <th>Tên nhóm</th>
                <th style="width: 130px">Phần mã trong mã ống</th>
            </tr>
        </thead>
        <tbody>
            @foreach (config('standard.groups') as $g)
                <tr>
                    <td class="text-center">{{ $g['no'] }}</td>
                    <td>{{ $g['short'] }}</td>
                    <td style="font-weight: 400">{{ $g['name'] }}</td>
                    <td><span class="ug-key">{{ $g['code'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h3>Các loại hạn dùng</h3>
<ul>
    <li><b>Hạn dùng xác định</b>: có hạn sử dụng in trên nhãn nhà sản xuất.</li>
    <li><b>Chưa xác định (Check online)</b>: nhà sản xuất không công bố hạn cố định; phải tra cứu hạn trực tuyến trước khi mở ống.</li>
    <li><b>Cần Retest định kỳ</b>: có hạn kiểm nghiệm lại (retest) do nhà sản xuất công bố, kèm chu kỳ retest.</li>
    <li><b>Cần xác định lại hạn dùng nội bộ</b>: dùng hạn nhà sản xuất kết hợp hạn nội bộ sau khi mở ống.</li>
    <li><b>Hạn dùng nội bộ</b> = ngày mở ống + số tháng hạn dùng mặc định, không bao giờ dài hơn hạn nhà sản xuất. Chuẩn có hạn dùng
        mặc định thì <b>phải xác định hạn nội bộ trước khi cấp phát và dùng</b>.</li>
</ul>

<h3>Xuất vượt và âm kho</h3>
<p class="mb-0">Mỗi lần dùng được vượt tồn còn lại tối đa <b>5%</b> để bù sai số cân. Phần vượt làm tồn bị âm, xử lý bằng
    <a href="#ton-kho">Cân đối</a>. Chỉ chọn được ống còn hạn, còn tồn và đã kiểm tra Đạt.</p>
