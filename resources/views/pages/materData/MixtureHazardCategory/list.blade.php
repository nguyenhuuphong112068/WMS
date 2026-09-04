@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - NHÓM NGUY HẠI BẢNG B (Phụ lục IV NĐ 24/2026/NĐ-CP)
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.mixtureHazardCategory.';
    $mdLabel = 'nhóm nguy hại Bảng B';
    $mdTitle = 'Nhóm Nguy Hại Bảng B';
    $mdIcon = 'fas fa-triangle-exclamation';
@endphp

@section('mainContent')
    @include('pages.materData.MixtureHazardCategory.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.MixtureHazardCategory.create')
    @include('pages.materData.MixtureHazardCategory.update')
@endsection
