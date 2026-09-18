@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ CẤP PHÁT VẬT TƯ
    |--------------------------------------------------------------------------
    | Phòng ban khai trước quy trình ký chuẩn của phiếu đề nghị cấp phát vật tư:
    | một TỔ HỢP điều kiện (phân loại của danh mục vật tư chung + phân loại của
    | danh mục vật tư phòng) ứng với một dãy BƯỚC KÝ, mỗi bước do một VAI TRÒ
    | phê duyệt. Màn hình luôn làm việc trên phòng ban đang chọn ở topNAV.
    */

    $mdRoute = 'pages.materData.materialSignFlow.';
    $mdLabel = 'quy trình trình ký';
    $mdTitle = 'Quy Trình Trình Ký';
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
