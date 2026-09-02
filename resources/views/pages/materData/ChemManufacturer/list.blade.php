@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - NHÀ SẢN XUẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.chemManufacturer.';
    $mdLabel = 'nhà sản xuất';
    $mdTitle = 'Nhà Sản Xuất';
    $mdIcon = 'fas fa-industry';
@endphp

@section('mainContent')
    @include('pages.materData.ChemManufacturer.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.ChemManufacturer.create')
    @include('pages.materData.ChemManufacturer.update')
@endsection
