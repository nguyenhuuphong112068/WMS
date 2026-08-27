@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | NHẬP - NHẬP CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable / create / update cùng đọc một nguồn.
    */

    $impRoute = 'pages.import.standardImport.';
    $impLabel = 'phiếu nhập chất chuẩn';
    $impIcon = 'fas fa-vial-circle-check';

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $impNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $impDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /**
     * Mã nhóm chuẩn (cột JSON standard_categories.groups) thành chuỗi "PRS,VKN"
     * để đưa vào data-groups cho bộ lọc Phân nhóm chuẩn.
     */
    $impGroups = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? implode(',', $codes) : '';
    };

    /*
    | standard_imports.group_code lưu MÃ NHÓM nằm trong mã ống chuẩn (VKN, IMP...),
    | không phải khoá của config('standard.groups') (VKN, IMPRS...). Hai bảng tra ngược
    | dưới đây dựng một lần rồi dùng lại cho cả bảng, khỏi lặp vòng lặp ở từng dòng.
    */
    $impKeyByCode = collect($groups)->mapWithKeys(fn($group, $key) => [$group['code'] => $key])->all();
    $impShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();

    /** Mã nhóm trong mã ống chuẩn -> tên viết tắt để hiện trên bảng. */
    $impGroupName = fn($code) => $impShortByCode[$code] ?? ($code ?: '—');

    /** Mã nhóm trong mã ống chuẩn -> khoá config, để form Cập Nhật gửi lại đúng nhóm. */
    $impGroupKey = fn($code) => $impKeyByCode[$code] ?? '';
@endphp

@section('mainContent')
    @include('pages.import.StandardImport.dataTable')
@endsection

@section('model')
    @include('pages.import.StandardImport.create')
    @include('pages.import.StandardImport.update')
    @include('pages.import.StandardImport.historyModal')
@endsection
