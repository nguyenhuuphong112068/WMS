@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - DANH MỤC VẬT TƯ
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.category.materialCategory.';
    $mdLabel = 'danh mục vật tư';
    $mdTitle = 'Danh Mục Vật Tư';
    $mdIcon = 'fas fa-cubes';
@endphp

@section('mainContent')
    @include('pages.category.MaterialCategory.dataTable')
@endsection

@section('model')
    @include('pages.category.MaterialCategory.create')
    @include('pages.category.MaterialCategory.update')
    @include('pages.category.shared.historyModal')
@endsection
