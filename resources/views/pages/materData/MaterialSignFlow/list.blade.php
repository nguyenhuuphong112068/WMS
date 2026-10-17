@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
    |--------------------------------------------------------------------------
    | Phòng ban khai các VAI TRÒ nhận trình ký: cấp ký, người ký và điều kiện
    | (nhóm VÀ, giữa các nhóm HOẶC) theo phân loại vật tư. Đề nghị đi qua các vai
    | trò được kích hoạt theo thứ tự cấp ký. Luôn làm việc trên phòng ban đang chọn.
    */

    $mdRoute = 'pages.materData.materialSignFlow.';
    $mdLabel = 'vai trò trình ký';
    $mdTitle = 'Vai Trò Trình Ký';
    $mdIcon = 'fas fa-file-signature';
@endphp

@section('mainContent')
    @include('pages.materData.MaterialSignFlow.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.MaterialSignFlow.create')
    @include('pages.materData.MaterialSignFlow.update')
@endsection
