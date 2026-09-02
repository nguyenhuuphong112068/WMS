@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TÊN HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.chemName.';
    $mdLabel = 'tên hoá chất';
    $mdTitle = 'Tên Hoá Chất';
    $mdIcon = 'fas fa-flask';
@endphp

@section('mainContent')
    @include('pages.materData.ChemName.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.ChemName.create')
    @include('pages.materData.ChemName.update')
@endsection
