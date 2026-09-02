@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | SỬ DỤNG - SỬ DỤNG VẬT TƯ
    |--------------------------------------------------------------------------
    | Vật tư bắt buộc qua đề nghị được phê duyệt (Trưởng/Phó Phòng bắt buộc, Ban Giám
    | Đốc tuỳ chọn) rồi kho cấp phát; loại bỏ hàng hỏng thì lập thẳng.
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
    @include('pages.export.MaterialExport.useModal')
    @foreach ($requestLists->whereIn('app_status', ['draft', 'rejected']) as $req)
        @include('pages.export.MaterialExport.requestEditModal', ['req' => $req, 'items' => $requestItems->get($req->id, collect())])
    @endforeach
    @foreach ($requestLists as $req)
        @include('pages.export.MaterialExport.requestDetailModal', ['req' => $req, 'items' => $requestItems->get($req->id, collect())])
    @endforeach
    {{-- Đặt sau các phiếu chi tiết: dựng dòng cấp phát + bảng chọn mã xuất nhập cho mọi mục còn chờ --}}
    @include('pages.export.MaterialExport.issueLotPickerModal')
@endsection
