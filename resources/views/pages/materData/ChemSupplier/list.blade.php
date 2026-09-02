@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - NHÀ CUNG CẤP
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.chemSupplier.';
    $mdLabel = 'nhà cung cấp';
    $mdTitle = 'Nhà Cung Cấp';
    $mdIcon = 'fas fa-truck';
@endphp

@section('mainContent')
    @include('pages.materData.ChemSupplier.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.ChemSupplier.create')
    @include('pages.materData.ChemSupplier.update')
@endsection
