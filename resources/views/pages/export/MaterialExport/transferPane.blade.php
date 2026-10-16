{{--
| SỬ DỤNG VẬT TƯ - TAB ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN
|
| Mô hình 3 bước (giống Sử Dụng Hoá Chất):
| 1. Phòng A (đang thiếu) tạo đề nghị, chọn phòng B (đang giữ) để gửi đến.
| 2. Phòng B thấy đề nghị ở bảng "cần cấp phát", chọn mã xuất nhập cụ thể + số lượng rồi
|    bấm Cấp phát: trừ tồn B ngay, mục chuyển sang "Chờ nhận" - CHƯA tạo tồn cho A.
|    B có thể Từ chối riêng từng mục kèm lý do nếu không cấp được.
| 3. Phòng A thấy mục "Chờ nhận" ở bảng "đã gửi đi", tự chọn định khu của phòng mình rồi
|    bấm Nhận: lúc này mới thật sự tạo mã xuất nhập mới cho A (bắt buộc A đã khai vật tư
|    này trước). A cũng có thể Từ chối nhận để hoàn tồn lại cho B.
|
| Hai bảng gửi đi / gửi đến dựng ở transferLists (dùng chung với tab Đề nghị VPP).
--}}

<div class="exp-pane {{ $activeTab === 'transfer' ? 'is-active' : '' }}" id="mePaneTransfer">
    @include('pages.export.MaterialExport.transferLists', [
        'mtTab' => 'transfer',
        'mtPrefix' => 't',
        'mtTableId' => 'matTransfer',
        'mtSent' => $transferSent,
        'mtReceived' => $transferReceived,
        'mtToSign' => $transferToSign,
        'mtSentRange' => $transferSentRange,
        'mtSentPerPage' => $transferSentPerPage,
        'mtReceivedRange' => $transferReceivedRange,
        'mtReceivedPerPage' => $transferReceivedPerPage,
        'mtReceivedTitle' => 'Đề nghị phòng ban khác gửi đến - cần cấp phát',
        'mtEmptySent' => 'Phòng mình chưa gửi đề nghị liên phòng ban nào.',
        'mtEmptyReceived' => 'Chưa có phòng ban nào gửi đề nghị đến phòng mình.',
        'mtCreate' => ['target' => '#matTransferRequestModal', 'label' => 'Tạo đề nghị chuyển liên phòng ban'],
        // Phòng Hành Chính nhận đề nghị ở tab "Đề nghị VPP"
        'mtShowReceived' => ! $vppIsAdminDepartment,
    ])
</div>
