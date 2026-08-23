@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | NHẬP - NHẬP HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $impRoute = 'pages.import.chemicalImport.';
    $impLabel = 'phiếu nhập hoá chất';
    $impIcon = 'fas fa-flask';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $impNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $impDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /**
     * Mã phân loại (cột JSON chemical_categories.classification) thành chuỗi "PL2,N1"
     * để đưa vào data-classification cho bộ lọc Phụ lục / Nhóm hoá chất.
     */
    $impCls = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? implode(',', $codes) : '';
    };

    // Số liệu tổng của tab báo cáo
    $impReportTimes = $report->sum('times');
    $impReportTotalKg = $report->whereNotNull('total_kg')->sum('total_kg');
    $impReportNotConvertible = $report->whereNull('total_kg')->count();
@endphp

@section('mainContent')
    @include('pages.import.ChemicalImport.dataTable')
@endsection

@section('model')
    @include('pages.import.ChemicalImport.create')
    @include('pages.import.ChemicalImport.update')
    @include('pages.import.ChemicalImport.receive')
    @include('pages.import.ChemicalImport.rejectTransfer')
    @include('pages.import.ChemicalImport.historyModal')
@endsection
