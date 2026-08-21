@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - ĐƠN VỊ TÍNH
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.unit.';
    $mdLabel = 'đơn vị tính';
    $mdTitle = 'Đơn Vị Tính';
    $mdIcon = 'fas fa-balance-scale';
@endphp

@section('mainContent')
    @include('pages.materData.Unit.dataTable')
@endsection

@section('model')
    @include('pages.materData.Unit.create')
    @include('pages.materData.Unit.update')
@endsection
