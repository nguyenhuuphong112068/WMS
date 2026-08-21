@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - ĐIỀU KIỆN BẢO QUẢN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.storageCondition.';
    $mdLabel = 'điều kiện bảo quản';
    $mdTitle = 'Điều Kiện Bảo Quản';
    $mdIcon = 'fas fa-temperature-low';
@endphp

@section('mainContent')
    @include('pages.materData.StorageCondition.dataTable')
@endsection

@section('model')
    @include('pages.materData.StorageCondition.create')
    @include('pages.materData.StorageCondition.update')
@endsection
