@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TRÌNH KÝ ĐỀ NGHỊ LIÊN PHÒNG BAN (gồm Đề nghị VPP)
    |--------------------------------------------------------------------------
    | Mỗi dòng một bước ký: loại đề nghị + công ty của phòng đề nghị + thứ tự + vai trò +
    | phạm vi phòng ban. Xem App\Support\MaterialTransferSignFlow.
    */

    $mdRoute = 'pages.materData.transferSignFlow.';
    $mdLabel = 'bước ký';
    $mdTitle = 'Bước Ký Đề Nghị Liên Phòng Ban';
    $mdIcon = 'fas fa-route';
@endphp

@section('mainContent')
    @include('pages.materData.TransferSignFlow.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.TransferSignFlow.create')
    @include('pages.materData.TransferSignFlow.update')
@endsection
