@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TÊN HOẠT CHẤT (Phụ lục IV NĐ 24/2026/NĐ-CP)
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.activeIngredient.';
    $mdLabel = 'tên hoạt chất';
    $mdTitle = 'Tên Hoạt Chất';
    $mdIcon = 'fas fa-atom';
@endphp

@section('mainContent')
    @include('pages.materData.ActiveIngredient.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.ActiveIngredient.create')
    @include('pages.materData.ActiveIngredient.update')
@endsection
