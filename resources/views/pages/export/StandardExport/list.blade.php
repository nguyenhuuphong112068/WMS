@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | SỬ DỤNG - SỬ DỤNG CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $expRoute = 'pages.export.standardExport.';
    $expLabel = 'phiếu sử dụng chất chuẩn';
    $expIcon = 'fas fa-vials';

    // Dữ liệu ống chuẩn cho JS: mã ống + tồn còn lại + đơn vị theo từng import_id
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
     * Mã nhóm chuẩn (cột JSON standard_categories.groups) thành chuỗi "PRS,VKN"
     * để đưa vào data-groups cho bộ lọc Phân nhóm chuẩn.
     */
    $expGroups = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? implode(',', $codes) : '';
    };

    /** Mã nhóm trong mã ống chuẩn (VKN, IMP...) -> tên viết tắt để hiện trên bảng. */
    $expShortByCode = collect($standardGroups ?? [])->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $expGroupName = fn($code) => $expShortByCode[$code] ?? ($code ?: '—');

    $stdReqStatus = [
        'draft' => ['label' => 'Lưu tạm', 'class' => 'neutral'],
        'pending' => ['label' => 'Chờ cấp phát', 'class' => 'pending'],
        'partial' => ['label' => 'Cấp một phần', 'class' => 'warning'],
        'completed' => ['label' => 'Đã cấp đủ', 'class' => 'accepted'],
        'rejected' => ['label' => 'Từ chối', 'class' => 'rejected'],
        'issued' => ['label' => 'Đã cấp', 'class' => 'accepted'],
        'canceled' => ['label' => 'Đã huỷ', 'class' => 'rejected'],
    ];
    $stdReqBadge = fn($status) => $stdReqStatus[$status] ?? ['label' => $status, 'class' => 'pending'];
@endphp


@section('mainContent')
    @include('pages.export.StandardExport.dataTable')
@endsection

@section('model')
    @include('pages.export.StandardExport.historyModal')
    @include('pages.export.StandardExport.create')
    @include('pages.export.StandardExport.update')
    @include('pages.export.StandardExport.requestModal')
    @include('pages.export.StandardExport.requestEditModal')
    @include('pages.export.StandardExport.issueModal')
    @include('pages.export.StandardExport.requestDetailModal')
    @include('pages.export.StandardExport.inventoryPickerModal')
@include('pages.export.StandardExport.issuedStandardPickerModal')
@include('pages.export.StandardExport.inventoryImportPickerModal')
@include('pages.export.StandardExport.transferRequestModal')
@include('pages.export.StandardExport.transferRequestEditModal')
@include('pages.export.StandardExport.transferDetailModal')
@endsection
