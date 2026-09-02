@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - QUY CÁCH ĐÓNG GÓI
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.packagingSpecification.';
    $mdLabel = 'quy cách đóng gói';
    $mdTitle = 'Quy Cách Đóng Gói';
    $mdIcon = 'fas fa-box-open';
@endphp

@section('mainContent')
    @include('pages.materData.PackagingSpecification.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.PackagingSpecification.create')
    @include('pages.materData.PackagingSpecification.update')
@endsection
