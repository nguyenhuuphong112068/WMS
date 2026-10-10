@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | THỐNG KÊ - THỐNG KÊ CẤP PHÁT VẬT TƯ
    |--------------------------------------------------------------------------
    | Trong kỳ đã chọn, mỗi đối tượng đã tiêu thụ những vật tư nào, bao nhiêu, cho
    | mục đích gì - tính theo số THỰC CẤP của kho (material_exports type = export).
    */
    $stNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $stDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
    $stDateTime = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '—';
@endphp

@section('mainContent')
    @include('pages.statistic.MaterialIssue.dataTable')
@endsection
