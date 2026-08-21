@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TÊN VẬT TƯ
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.materialName.';
    $mdLabel = 'tên vật tư';
    $mdTitle = 'Tên Vật Tư';
    $mdIcon = 'fas fa-cubes';
@endphp

@section('mainContent')
    @include('pages.materData.MaterialName.dataTable')
@endsection

@section('model')
    @include('pages.materData.MaterialName.create')
    @include('pages.materData.MaterialName.update')
@endsection
