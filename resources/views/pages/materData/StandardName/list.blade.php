@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TÊN CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.standardName.';
    $mdLabel = 'tên chuẩn';
    $mdTitle = 'Tên Chuẩn';
    $mdIcon = 'fas fa-flask';
@endphp

@section('mainContent')
    @include('pages.materData.StandardName.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.StandardName.create')
    @include('pages.materData.StandardName.update')
@endsection
