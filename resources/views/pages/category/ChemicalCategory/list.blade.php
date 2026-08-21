@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DANH MỤC - DANH MỤC HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.category.chemicalCategory.';
    $mdLabel = 'danh mục hoá chất';
    $mdTitle = 'Danh Mục Hoá Chất';
    $mdIcon = 'fas fa-flask';

    // Nhóm phân loại tô đỏ để cảnh báo (hoá chất cấm)
    $mdDangerCodes = ['CAM', 'N4', 'N6'];
@endphp

@section('mainContent')
    @include('pages.category.ChemicalCategory.dataTable')
@endsection

@section('model')
    @include('pages.category.ChemicalCategory.create')
    @include('pages.category.ChemicalCategory.update')
    @include('pages.category.ChemicalCategory.convert')
    @include('pages.category.shared.historyModal')
@endsection
