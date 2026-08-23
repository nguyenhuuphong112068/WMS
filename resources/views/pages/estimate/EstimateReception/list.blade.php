@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỰ TRÙ - TIẾP NHẬN DỰ TRÙ (bộ phận Cung Ứng)
    |--------------------------------------------------------------------------
    */

    $estRoute = 'pages.estimate.estimateReception.';
    $estLabel = 'phiếu dự trù';
    $estTitle = 'Tiếp Nhận Dự Trù';
    $estIcon = 'fas fa-truck-ramp-box';
@endphp

@section('mainContent')
    @include('pages.estimate.EstimateReception.dataTable')
@endsection

@section('model')
    @include('pages.estimate.EstimateReception.reception')
    @include('pages.estimate.shared.historyModal')
@endsection
