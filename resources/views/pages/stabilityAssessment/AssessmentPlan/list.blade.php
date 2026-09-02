@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | ĐÁNH GIÁ HẠN DÙNG - KẾ HOẠCH ĐÁNH GIÁ
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable đọc cùng một nguồn, giống cách list của Chuẩn
    | Thứ Cấp đang làm.
    |
    | Biến vào: $datas, $period, $months, $stateCounts, $itemStates, $itemInitial,
    |           $dueSoonDays, $groups, $unplanned, $assessGroupName, $assessGroupCode
    |
    | Màn hình CHỈ ĐỌC: mọi thao tác ghi kết quả nằm ở trang chi tiết phiếu bên
    | Chuẩn Thứ Cấp, đây chỉ có đường dẫn sang.
    */

    $planRoute = 'pages.stabilityAssessment.assessmentPlan.';
    $ssaRoute = 'pages.stabilityAssessment.standardStability.';

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $planDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /** Trạng thái phiếu -> lớp CSS của thẻ .ssa-badge (dùng chung với Chuẩn Thứ Cấp). */
    $planStatusClass = fn($status) => match ($status) {
        'Đang Đánh Giá' => 'running',
        'Hoàn Thành' => 'done',
        'Dừng Đánh Giá' => 'stopped',
        'Huỷ' => 'cancelled',
        default => 'initial',
    };

    /** Mã nhóm trong mã ống chuẩn (CTC, VKN...) -> tên viết tắt để hiện trên bảng. */
    $planShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $planGroupName = fn($code) => $planShortByCode[$code] ?? ($code ?: '—');

    $planLabel = $planDate($period['from']) . ' - ' . $planDate($period['to']);

    // Số ống chuẩn có mặt trong kế hoạch - một ống có thể có nhiều mốc trong cùng khoảng
    $planImportCount = $datas->pluck('import_code')->unique()->count();

    // Việc thật sự phải làm trong khoảng này: mốc chưa có kết quả mà đã quá hạn hoặc sắp đến hạn
    $planTodo = $stateCounts['overdue'] + $stateCounts['due'];

    /*
    | Mốc chọn nhanh. Kế hoạch nhìn về phía trước nên các mốc đều lấy TRỌN tháng /
    | quý / năm, không cắt ngang ở hôm nay.
    |
    | "Đã quá hạn" nhìn ngược lại 24 tháng để gom hết mốc còn nợ chưa đánh giá.
    */
    $planToday = \Carbon\Carbon::today();

    $planPresets = collect([
        [
            'label' => 'Tháng này',
            'from' => $planToday->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $planToday->copy()->endOfMonth()->format('Y-m-d'),
        ],
        [
            'label' => 'Quý này',
            'from' => $planToday->copy()->startOfQuarter()->format('Y-m-d'),
            'to' => $planToday->copy()->endOfQuarter()->format('Y-m-d'),
        ],
        [
            'label' => '3 tháng tới',
            'from' => $planToday->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $planToday->copy()->addMonthsNoOverflow(3)->endOfMonth()->format('Y-m-d'),
        ],
        [
            'label' => '6 tháng tới',
            'from' => $planToday->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $planToday->copy()->addMonthsNoOverflow(6)->endOfMonth()->format('Y-m-d'),
        ],
        [
            'label' => 'Năm nay',
            'from' => $planToday->copy()->startOfYear()->format('Y-m-d'),
            'to' => $planToday->copy()->endOfYear()->format('Y-m-d'),
        ],
        [
            'label' => 'Đã quá hạn',
            'from' => $planToday->copy()->subMonthsNoOverflow(24)->startOfMonth()->format('Y-m-d'),
            'to' => $planToday->copy()->subDay()->format('Y-m-d'),
        ],
    ])
        ->map(fn($preset) => $preset + ['active' => $preset['from'] === $period['from'] && $preset['to'] === $period['to']])
        ->all();
@endphp

@section('mainContent')
    @include('pages.stabilityAssessment.AssessmentPlan.dataTable')
@endsection
