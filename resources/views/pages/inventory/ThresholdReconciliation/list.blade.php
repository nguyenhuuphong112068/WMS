@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | TỒN - ĐỐI CHIẾU NGƯỠNG PHỤ LỤC IV NĐ 24/2026/NĐ-CP
    |--------------------------------------------------------------------------
    | Màn hình chỉ đọc. Số liệu cộng toàn công ty theo từng hoạt chất, tính ở
    | App\Support\ActiveIngredientThreshold.
    */
    $trNum = fn($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
@endphp

@section('mainContent')
    @include('pages.inventory.ThresholdReconciliation.dataTable')
@endsection
