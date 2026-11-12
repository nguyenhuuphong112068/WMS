@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | SỬ DỤNG - SỬ DỤNG VPP
    |--------------------------------------------------------------------------
    | Cơ chế "Sử dụng - ghi sổ" như Hoá Chất (từ 12/11/2026): bấm Sử dụng VPP, chọn lô rồi Lưu là
    | ghi vào Sổ sử dụng VPP và trừ tồn ngay - không đề nghị nội bộ / trình ký / Phiếu Tạm.
    | Giữ đề nghị VPP liên phòng ban + đề nghị VPP đến kho Hành Chính.
    | Loại bỏ hàng hỏng đi 3 bước ở tab "Cách ly - Loại Bỏ - Huỷ": Cách ly -> Loại bỏ -> Huỷ.
    */

    $expRoute = 'pages.export.vppExport.';
    $expLabel = 'phiếu sử dụng VPP';
    $expIcon = 'fas fa-hand-holding-medical';

    $expNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $expDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $expDateTime = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';
@endphp

@section('mainContent')
    @include('pages.export.VppExport.dataTable')
@endsection

@section('model')
    @include('pages.export.VppExport.historyModal')
    @include('pages.export.VppExport.update')
    @include('pages.export.VppExport.useModal')

    {{-- Tab "Cách ly - Loại Bỏ - Huỷ" --}}
    @include('pages.export.VppExport.quarantineModals')

    {{-- Tab "Đề nghị chuyển liên phòng ban" + tab "VPP đến HC" (cùng bộ bảng, khác phòng nhận) --}}
    @include('pages.export.VppExport.transferRequestModal')
    @include('pages.export.VppExport.transferRequestEditModal')
    @include('pages.export.VppExport.transferDetailModal')
    @include('pages.export.VppExport.transferReceiveCheckModal')
    @include('pages.export.VppExport.transferStockPickerModal')
    @include('pages.export.VppExport.transferImportPickerModal')
    @include('pages.export.VppExport.vppWatchlistModal')

    {{--
    | Link "chờ bạn ký duyệt" ở Trang chủ mở thẳng phiếu: ?open=<id modal chi tiết>
    | (HomeController::signUrl). Chỉ nhận modal chi tiết đề nghị liên phòng ban / VPP đến HC, rồi
    | bỏ tham số khỏi URL để tải lại trang không bật lại modal.
    --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var params = new URLSearchParams(window.location.search);
            var open = params.get('open') || '';

            if (!/^matTransferDetailModal_\d+$/.test(open)) {
                return;
            }

            params.delete('open');
            window.history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : ''));

            var $modal = $('#' + open);

            if ($modal.length) {
                $modal.modal('show');
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Không mở được phiếu',
                    text: 'Phiếu không nằm trong danh sách đang hiện (đã được xử lý hoặc ngoài khoảng ngày lọc).'
                });
            }
        });
    </script>

@endsection
