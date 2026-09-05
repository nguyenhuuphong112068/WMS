@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | SỬ DỤNG - SỬ DỤNG HOÁ CHẤT
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $expRoute = 'pages.export.chemicalExport.';
    $expLabel = 'phiếu sử dụng hoá chất';
    $expIcon = 'fas fa-vials';

    // Bước 2 của nghiệp vụ huỷ bỏ: gom phiếu loại bỏ thành đợt xin quyết định huỷ
    $dspRoute = 'pages.export.chemicalDisposal.';

    // Dữ liệu phiếu nhập cho JS: mã xuất nhập + tồn còn lại + hạn mức xuất theo từng import_id
    $expImportMap = $imports
        ->mapWithKeys(
            fn($import) => [
                $import->id => [
                    'code' => $import->code,
                    'remaining' => (float) $import->remaining,
                    'unit' => $import->unit_short_name ?: '',
                ],
            ],
        )
        ->toArray();

    // Phần trăm được xuất vượt tồn, JS dùng để tính hạn mức ngay trên form
    $expOverRatio = $overIssuePercent / 100;

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $expNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $expDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /**
     * Nhóm NĐ 24/2026 (suy tự động, mã N1..N10) của một mã danh mục -> chuỗi "N3,N9"
     * để đưa vào data-classification cho bộ lọc Phụ lục / Nhóm hoá chất.
     */
    $expCls = function ($categoryId) use ($classificationCodes) {
        return implode(',', $classificationCodes[$categoryId] ?? []);
    };

    // Số liệu tổng của tab báo cáo
    $expReportTimes = $report->sum('times');
    $expReportTotalKg = $report->whereNotNull('total_kg')->sum('total_kg');
    $expReportNotConvertible = $report->whereNull('total_kg')->count();
@endphp

@section('mainContent')
    @include('pages.export.ChemicalExport.dataTable')
@endsection

@section('model')
    @include('pages.export.shared.historyModal')
    @include('pages.export.ChemicalExport.requestModal')
    @include('pages.export.ChemicalExport.disposalModal')
    @include('pages.export.ChemicalExport.disposalDecideModal')
    @include('pages.export.ChemicalExport.disposalCompleteModal')
    @include('pages.export.ChemicalExport.create')
    @include('pages.export.ChemicalExport.update')
@endsection
