@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | NHẬP - NHẬP VẬT TƯ
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $impRoute = 'pages.import.materialImport.';
    $impLabel = 'phiếu nhập vật tư';
    $impIcon = 'fas fa-box-open';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $impNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $impDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';
@endphp

@section('mainContent')
    @include('pages.import.MaterialImport.dataTable')
@endsection

@section('model')
    @include('pages.import.MaterialImport.create')
    @include('pages.import.MaterialImport.update')
    @include('pages.import.MaterialImport.historyModal')
@endsection
