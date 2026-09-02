@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - PHÂN LOẠI VẬT TƯ
    |--------------------------------------------------------------------------
    | Mỗi phòng ban tự khai bộ nhóm phân loại của phòng mình. Màn hình luôn làm
    | việc trên phòng ban đang chọn ở topNAV.
    */

    $mdRoute = 'pages.materData.materialClassification.';
    $mdLabel = 'phân loại vật tư';
    $mdTitle = 'Phân Loại Vật Tư';
    $mdIcon = 'fas fa-tags';
@endphp

@section('mainContent')
    @include('pages.materData.MaterialClassification.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.MaterialClassification.create')
    @include('pages.materData.MaterialClassification.update')
@endsection
