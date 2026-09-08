@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | SỬ DỤNG - SỬ DỤNG VẬT TƯ
    |--------------------------------------------------------------------------
    | Vật tư bắt buộc qua đề nghị được phê duyệt rồi kho cấp phát; số bước ký và người ký
    | từng bước do người lập phiếu tự khai (0 bước = duyệt thẳng đến người cấp phát).
    | Loại bỏ hàng hỏng thì lập thẳng, không cần đề nghị.
    */

    $expRoute = 'pages.export.materialExport.';
    $expLabel = 'phiếu sử dụng vật tư';
    $expIcon = 'fas fa-hand-holding-medical';

    $expNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $expDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $expDateTime = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';

    $expReqBadge = fn($status) => match ($status) {
        'approved' => ['label' => $reqAppStatuses['approved'] ?? 'Đã duyệt', 'class' => 'approved'],
        'rejected' => ['label' => $reqAppStatuses['rejected'] ?? 'Bị từ chối', 'class' => 'rejected'],
        'canceled' => ['label' => $reqAppStatuses['canceled'] ?? 'Đã huỷ', 'class' => 'rejected'],
        'draft' => ['label' => $reqAppStatuses['draft'] ?? 'Nháp', 'class' => 'pending'],
        default => ['label' => $reqAppStatuses[$status] ?? $status, 'class' => 'pending'],
    };

@endphp

@section('mainContent')
    @include('pages.export.MaterialExport.dataTable')
@endsection

@section('model')
    @include('pages.export.MaterialExport.historyModal')
    @include('pages.export.MaterialExport.create')
    @include('pages.export.MaterialExport.reject')
    @include('pages.export.MaterialExport.requestModal')
    @include('pages.export.MaterialExport.inventoryPickerModal')
    @foreach ($requestLists->whereIn('app_status', ['draft', 'rejected']) as $req)
        @include('pages.export.MaterialExport.requestEditModal', ['req' => $req, 'items' => $requestItems->get($req->id, collect())])
    @endforeach
    @foreach ($requestLists as $req)
        @include('pages.export.MaterialExport.requestDetailModal', ['req' => $req, 'items' => $requestItems->get($req->id, collect())])
    @endforeach
    {{-- Đặt sau các phiếu chi tiết: dựng dòng cấp phát + bảng chọn mã xuất nhập cho mọi mục còn chờ --}}
    @include('pages.export.MaterialExport.issueLotPickerModal')

    {{-- Tab "Đề nghị chuyển liên phòng ban" --}}
    @include('pages.export.MaterialExport.transferRequestModal')
    @include('pages.export.MaterialExport.transferRequestEditModal')
    @include('pages.export.MaterialExport.transferDetailModal')
    @include('pages.export.MaterialExport.transferStockPickerModal')
    @include('pages.export.MaterialExport.transferImportPickerModal')

    {{-- Tab "Ký duyệt (mọi phòng ban)" - xem nhanh nội dung phiếu chờ ký --}}
    @foreach ($inboxRequests as $req)
        @include('pages.export.MaterialExport.inboxDetailModal', [
            'req' => $req,
            'items' => $inboxItems->get($req->id, collect()),
            'signs' => $inboxSigns->get($req->id, collect()),
        ])
    @endforeach
@endsection
