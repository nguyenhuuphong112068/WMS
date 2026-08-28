@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | ĐÁNH GIÁ HẠN DÙNG - CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    |
    | Biến vào: $datas, $imports, $statuses, $itemStates, $groups, $dueSoonDays
    */

    $ssaRoute = 'pages.stabilityAssessment.standardStability.';
    $ssaLabel = 'phiếu đánh giá hạn dùng';
    $ssaIcon = 'fas fa-clipboard-list';

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $ssaDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /** Trạng thái phiếu -> lớp CSS của thẻ .ssa-badge. */
    $ssaStatusClass = fn($status) => match ($status) {
        'Đang Đánh Giá' => 'running',
        'Hoàn Thành' => 'done',
        'Huỷ' => 'cancelled',
        default => 'initial',
    };

    /** Mã nhóm trong mã ống chuẩn (VKN, IMP...) -> tên viết tắt để hiện trên bảng. */
    $ssaShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $ssaGroupName = fn($code) => $ssaShortByCode[$code] ?? ($code ?: '—');

    // Số phiếu theo từng trạng thái, dùng cho các nút lọc nhanh
    $ssaStatusCounts = collect($statuses)
        ->mapWithKeys(fn($status) => [$status => $datas->where('status', $status)->count()])
        ->all();
@endphp

@section('mainContent')
    @include('pages.stabilityAssessment.StandardStability.dataTable')
@endsection

@section('model')
    @include('pages.stabilityAssessment.StandardStability.create')
    @include('pages.stabilityAssessment.StandardStability.update')
@endsection
