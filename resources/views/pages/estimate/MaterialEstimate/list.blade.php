@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỰ TRÙ - DỰ TRÙ VẬT TƯ
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update / reject cùng đọc một nguồn.
    | Luồng trình ký 2 bước dùng chung khai báo config/estimate.php với dự trù hoá chất.
    */

    $estRoute = 'pages.estimate.materialEstimate.';
    $estLabel = 'phiếu dự trù vật tư';
    $estTitle = 'Dự Trù Vật Tư';
    $estIcon = 'fas fa-clipboard-check';
@endphp

@section('mainContent')
    @include('pages.estimate.MaterialEstimate.dataTable')
@endsection

@section('model')
    @include('pages.estimate.MaterialEstimate.create')
    @include('pages.estimate.MaterialEstimate.update')
    @include('pages.estimate.MaterialEstimate.reject')
    @include('pages.estimate.shared.historyModal')
@endsection
