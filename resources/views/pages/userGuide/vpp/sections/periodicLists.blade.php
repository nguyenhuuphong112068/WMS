@include('pages.userGuide.shared.flowMapLink', ['lane' => 'lien-phong-ban', 'no' => 2, 'label' => 'Đề nghị liên phòng ban'])
@include('pages.userGuide.shared.flowMapLink', ['lane' => 'du-tru', 'no' => 1, 'label' => 'Dự trù mua hàng'])

<p>Lập sẵn một danh sách VPP dùng đều đặn. Đến ngày đã hẹn, hệ thống <b>tự tạo</b> một phiếu ở trạng thái
    <b>Lưu tạm / Nháp</b> để bạn kiểm tra lại rồi gửi đi. Có 2 tab:</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/danh-sach-chu-ky.png', 'caption' => 'Màn hình Danh Mục › VPP › 2 tab DS ... theo chu kỳ'])

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
                <td>DS đề nghị liên phòng ban theo chu kỳ</td>
                <td style="font-weight: 400">Đề nghị <a href="#lien-phong-ban">liên phòng ban</a> gửi tới <b>Phòng Cấp Phát</b> đã chọn.
                    Thường gửi tới Hành Chánh (kho VPP).</td>
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
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/vpp/danh-sach-chu-ky-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Sách Theo Chu Kỳ'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tiêu Đề', true, 'Ví dụ: <i>VPP bảo trì hằng tháng</i>.'],
    ['Chu Kỳ', true, 'Hằng tuần, Hằng tháng, 2 tháng/lần, Hằng quý, 6 tháng/lần, Theo số năm, Theo số ngày.'],
    ['Ngày Tạo Đề Nghị Trong Chu Kỳ', true, '<b>Ngày Cụ Thể</b>: ví dụ ngày 5 hằng tháng; ngày vượt độ dài kỳ (31 ở tháng 30 ngày) thì lấy ngày cuối kỳ.'],
    ['Ngày Bắt Đầu Chu Kỳ Đầu Tiên', true, 'Không được lùi về quá khứ. Hệ thống không bao giờ tạo phiếu trước ngày này.'],
    ['VPP Đề Nghị / Dự Trù', true, 'Chọn VPP, số lượng, mục đích. Đơn vị theo <a href="#danh-muc-cong-ty">Danh mục VPP công ty</a>.'],
]])

<p>VPP không có danh sách đề nghị <b>nội bộ</b> theo chu kỳ: dùng VPP trong phòng thì ghi thẳng ở tab
    <a href="#su-dung-vpp">Sử Dụng VPP</a>.</p>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>
        <ul class="mb-0 pl-3">
            <li>Cần phiếu ngay mà chưa tới hạn? Bấm <span class="btn btn-sm btn-info ug-ui"><i class="fas fa-file-medical"></i></span>
                (Tạo ngay) trên dòng. Lịch tự động vẫn giữ nguyên.</li>
            <li>Máy chủ tắt nhiều kỳ thì lần chạy lại chỉ tạo <b>một</b> phiếu rồi dời sang kỳ kế tiếp, không tạo dồn.</li>
            <li>Mở màn Danh Mục VPP thì hệ thống bù ngay các danh sách đã tới hạn của phòng.</li>
        </ul>
    </div>
</div>
