@include('pages.userGuide.shared.flowMapLink', ['lane' => 'noi-bo', 'no' => 3, 'label' => 'Đề nghị sử dụng nội bộ'])
@include('pages.userGuide.shared.flowMapLink', ['lane' => 'lien-phong-ban', 'no' => 2, 'label' => 'Đề nghị liên phòng ban'])
@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Lập sẵn một danh sách vật tư dùng đều đặn. Đến ngày đã hẹn, hệ thống <b>tự tạo</b> một phiếu ở trạng thái
    <b>Lưu tạm / Nháp</b> để bạn kiểm tra lại rồi gửi đi. Có 3 tab:</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 240px">Tab</th>
                <th>Đến hạn hệ thống tạo gì</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>DS đề nghị nội bộ theo chu kỳ</td>
                <td style="font-weight: 400">Đề nghị cấp phát nội bộ (tab <a href="#de-nghi-noi-bo">Đề nghị Theo Định Kỳ</a>). Có thể gắn
                    với một <b>Đối Tượng</b> (thiết bị); khi đó chỉ chọn được tần suất của đối tượng đó.</td>
            </tr>
            <tr>
                <td>DS đề nghị liên phòng ban theo chu kỳ</td>
                <td style="font-weight: 400">Đề nghị <a href="#lien-phong-ban">liên phòng ban</a> gửi tới <b>Phòng Cấp Phát</b> đã chọn.
                    Gửi tới Hành Chánh thì chỉ được chọn vật tư do Hành Chánh mua.</td>
            </tr>
            <tr>
                <td>DS dự trù theo chu kỳ</td>
                <td style="font-weight: 400"><a href="#du-tru">Phiếu dự trù</a> ở trạng thái Nháp, dự trù cho tháng ở ô <b>Dự Trù Cho</b>
                    (tháng tạo phiếu, tháng sau, 2 hoặc 3 tháng sau).</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Các ô nhập chính</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tiêu Đề', true, 'Ví dụ: <i>Vật tư bảo trì hằng tháng</i>.'],
    ['Chu Kỳ', true, 'Hằng tuần, Hằng tháng, 2 tháng/lần, Hằng quý, 6 tháng/lần, Theo số năm, Theo số ngày. Danh sách nội bộ không chọn chu kỳ mà chọn <b>Tần Suất Đề Nghị</b> (hằng ngày ... 7 năm/lần).'],
    ['Ngày Tạo Đề Nghị Trong Chu Kỳ', true, '<b>Ngày Cụ Thể</b>: ví dụ ngày 5 hằng tháng; ngày vượt độ dài kỳ (31 ở tháng 30 ngày) thì lấy ngày cuối kỳ. Đối tượng đồng bộ từ CAL có thể chọn <b>theo hạn lịch CAL</b> và <b>Tạo Trước</b> 0 - 30 ngày.'],
    ['Ngày Bắt Đầu Chu Kỳ Đầu Tiên', true, 'Không được lùi về quá khứ. Hệ thống không bao giờ tạo phiếu trước ngày này.'],
    ['Vật Tư Đề Nghị / Dự Trù', true, 'Chọn vật tư, số lượng, đơn vị, mục đích. Phiếu dự trù bắt buộc có đơn vị cho từng dòng.'],
]])

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Cần phiếu ngay mà chưa tới hạn? Bấm <span class="btn btn-sm btn-info ug-ui"><i class="fas fa-file-medical"></i></span>
                (Tạo ngay) trên dòng. Lịch tự động vẫn giữ nguyên.</li>
            <li>Máy chủ tắt nhiều kỳ thì lần chạy lại chỉ tạo <b>một</b> phiếu rồi dời sang kỳ kế tiếp, không tạo dồn.</li>
            <li>Mở màn Danh Mục hoặc Sử Dụng Vật Tư thì hệ thống bù ngay các danh sách đã tới hạn của phòng.</li>
        </ul>
    </div>
</div>
