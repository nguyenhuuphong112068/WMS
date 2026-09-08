@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỰ TRÙ - DỰ TRÙ CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update / reject cùng đọc một nguồn.
    | Luồng trình ký 2 bước dùng chung khai báo config/estimate.php với dự trù hoá chất.
    */

    $estRoute = 'pages.estimate.standardEstimate.';
    $estLabel = 'phiếu dự trù chất chuẩn';
    $estTitle = 'Dự Trù Chất Chuẩn';
    $estIcon = 'fas fa-clipboard-check';
@endphp

@section('mainContent')
    @include('pages.estimate.StandardEstimate.dataTable')
@endsection

@section('model')
    @include('pages.estimate.StandardEstimate.create')
    @include('pages.estimate.StandardEstimate.update')
    @include('pages.estimate.StandardEstimate.reject')
    @include('pages.estimate.shared.submitModal')
    @include('pages.estimate.shared.historyModal')

    {{-- Tab "Ký duyệt (mọi phòng ban)" - xem nhanh nội dung phiếu chờ ký --}}
    @if ($showApprovalInbox)
        @foreach ($inboxRequests as $req)
            @include('pages.estimate.shared.inboxDetailModal', [
                'req' => $req,
                'items' => $inboxItems->get($req->id, collect()),
                'signs' => $inboxSigns->get($req->id, collect()),
            ])
        @endforeach
    @endif
@endsection
