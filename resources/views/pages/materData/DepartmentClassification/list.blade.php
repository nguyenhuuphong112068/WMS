@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - PHÂN LOẠI (THEO PHÒNG BAN)
    |--------------------------------------------------------------------------
    | Mỗi phòng ban tự khai bộ nhóm phân loại của phòng mình, dùng khi khai
    | "Vật Tư Của Phòng". Màn hình luôn làm việc trên phòng ban đang chọn ở topNAV.
    */

    $mdRoute = 'pages.materData.departmentClassification.';
    $mdLabel = 'phân loại';
    $mdTitle = 'Phân Loại';
    $mdIcon = 'fas fa-tags';
@endphp

@section('mainContent')
    @include('pages.materData.DepartmentClassification.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.DepartmentClassification.create')
    @include('pages.materData.DepartmentClassification.update')
@endsection
