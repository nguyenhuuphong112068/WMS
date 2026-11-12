@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỰ TRÙ - DỰ TRÙ VPP
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update / reject cùng đọc một nguồn.
    | Luồng trình ký 2 bước dùng chung khai báo config/estimate.php với dự trù hoá chất.
    */

    $estRoute = 'pages.estimate.vppEstimate.';
    $estLabel = 'phiếu dự trù VPP';
    $estTitle = 'Dự Trù VPP';
    $estIcon = 'fas fa-clipboard-check';
@endphp

@section('mainContent')
    @include('pages.estimate.VppEstimate.dataTable')
@endsection

@section('model')
    @include('pages.estimate.VppEstimate.create')
    @include('pages.estimate.VppEstimate.update')
    @include('pages.estimate.VppEstimate.reject')

    {{-- Lập phiếu dự trù từ các VPP chọn ở tab "Danh sách VPP cần dự trù" --}}
    @include('pages.estimate.VppEstimate.watchlistEstimate')

    @include('pages.estimate.shared.submitModal')
    @include('pages.estimate.shared.historyModal')

@endsection
