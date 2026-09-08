@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỰ TRÙ - DỰ TRÙ HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update / reject cùng đọc một nguồn.
    */

    $estRoute = 'pages.estimate.chemicalEstimate.';
    $estLabel = 'phiếu dự trù hoá chất';
    $estTitle = 'Dự Trù Hoá Chất';
    $estIcon = 'fas fa-clipboard-check';
@endphp

@section('mainContent')
    @include('pages.estimate.ChemicalEstimate.dataTable')
@endsection

@section('model')
    @include('pages.estimate.ChemicalEstimate.create')
    @include('pages.estimate.ChemicalEstimate.update')
    @include('pages.estimate.ChemicalEstimate.reject')
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
