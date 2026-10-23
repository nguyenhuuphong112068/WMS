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

    {{-- Lập phiếu dự trù từ các hoá chất chọn ở tab "Danh sách hoá chất cần dự trù" --}}
    @if ($tabAllowed['watchlist'])
        @include('pages.estimate.shared.watchlistEstimate', ['wlKind' => 'chemical'])
    @endif
    @include('pages.estimate.shared.submitModal')
    @include('pages.estimate.shared.historyModal')

@endsection
