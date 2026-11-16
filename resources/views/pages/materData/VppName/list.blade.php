@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | DỮ LIỆU GỐC - TÊN VPP
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $mdRoute = 'pages.materData.vppName.';
    $mdLabel = 'tên VPP';
    $mdTitle = 'Tên VPP';
    $mdIcon = 'fas fa-paperclip';
@endphp

@section('mainContent')
    @include('pages.materData.VppName.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.VppName.create')
    @include('pages.materData.VppName.update')
@endsection
