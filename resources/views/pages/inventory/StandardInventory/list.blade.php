@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | TỒN - TỒN KHO CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable và các modal cùng đọc một nguồn.
    | Màn hình chỉ đọc phần tồn, chỉ ghi dữ liệu qua nút Cân Đối và Hạn Nội Bộ.
    */

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /*
    |--------------------------------------------------------------------------
    | KỲ BÁO CÁO
    |--------------------------------------------------------------------------
    | $period do controller tính (mặc định từ đầu tháng đến hôm nay). Bảng tồn đọc
    | bốn chỉ số theo kỳ: Tồn Đầu Kỳ - Nhập Trong Kỳ - Sử Dụng / Huỷ Trong Kỳ -
    | Tồn Cuối Kỳ.
    */
    $invToday = \Carbon\Carbon::today();
    $invPeriodLabel = $invDate($period['from']) . ' - ' . $invDate($period['to']);

    /*
    | Mốc chọn nhanh, mỗi mốc là TRỌN kỳ (đến ngày cuối tháng / quý / năm) cho khớp
    | với kỳ mặc định. Ngày cuối kỳ ở tương lai không làm sai số liệu vì chưa có
    | phát sinh nào sau hôm nay.
    |
    | "Toàn bộ" lấy từ ngày nhập xa nhất đang có nên tồn đầu kỳ bằng 0 và mọi phát
    | sinh đều nằm trong kỳ - đúng bằng cách xem tồn trước đây.
    */
    $invEarliest = $datas->min('imported_date');
    $invEarliest = $invEarliest ? substr((string) $invEarliest, 0, 10) : $invToday->copy()->startOfYear()->format('Y-m-d');

    $invPeriodPresets = collect([
        [
            'label' => 'Tháng này',
            'from' => $invToday->copy()->startOfMonth()->format('Y-m-d'),
            'to' => $invToday->copy()->endOfMonth()->format('Y-m-d'),
        ],
        [
            'label' => 'Tháng trước',
            'from' => $invToday->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            'to' => $invToday->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d'),
        ],
        [
            'label' => 'Quý này',
            'from' => $invToday->copy()->startOfQuarter()->format('Y-m-d'),
            'to' => $invToday->copy()->endOfQuarter()->format('Y-m-d'),
        ],
        [
            'label' => 'Năm nay',
            'from' => $invToday->copy()->startOfYear()->format('Y-m-d'),
            'to' => $invToday->copy()->endOfYear()->format('Y-m-d'),
        ],
        [
            'label' => 'Toàn bộ',
            'from' => $invEarliest,
            'to' => $invToday->copy()->endOfMonth()->format('Y-m-d'),
        ],
    ])
        ->map(fn($preset) => $preset + ['active' => $preset['from'] === $period['from'] && $preset['to'] === $period['to']])
        ->all();

    /** Hạn dùng nhà sản xuất chưa xác định, tra cứu trực tuyến khi dùng (standard_imports.expiry_type). */
    $invIsCheckOnline = fn($row) => in_array($row->expiry_type ?? null, ['check online', 'undetermined', 'unlimited']);

    /**
     * Mã nhóm chuẩn (cột JSON standard_categories.groups) thành chuỗi "PRS,VKN"
     * để đưa vào data-groups cho bộ lọc Phân nhóm chuẩn.
     */
    $invGroups = function ($value) {
        $codes = json_decode($value ?? '', true);

        return is_array($codes) ? implode(',', $codes) : '';
    };

    /** Mã nhóm trong mã ống chuẩn (VKN, IMP...) -> tên viết tắt để hiện trên bảng. */
    $invShortByCode = collect($groups)->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();
    $invGroupName = fn($code) => $invShortByCode[$code] ?? ($code ?: '—');

    /*
    | Cách tính từng trạng thái tồn, đưa vào tooltip của nút lọc thay vì in thành
    | một hàng chú thích riêng trên màn hình.
    */
    $invStateHints = [
        'in' => 'Còn hàng: tồn còn trên ' . $lowStockPercent . '% lượng nhập và còn hạn dùng.',
        'low' => 'Sắp hết: tồn còn từ ' . $lowStockPercent . '% lượng nhập trở xuống.',
        'near' => 'Sắp hết hạn: còn hạn dùng dưới ' . $nearExpiryDays . ' ngày.',
        'expired' => 'Hết hạn: đã quá hạn dùng nhưng vẫn còn tồn.',
        'out' => 'Hết hàng: đã dùng hết, tồn bằng 0.',
        'over' => 'Âm kho: đã xuất vượt lượng nhập - bấm Cân Đối để chỉnh số lượng nhập về đúng thực tế.',
    ];

    // Số mã ống chuẩn theo từng trạng thái tồn, dùng cho các nút lọc nhanh
    $invStateCounts = collect($states)
        ->map(fn($label, $key) => $datas->where('state', $key)->count())
        ->toArray();

    // Lịch sử cân đối cho JS: [import_id => [{số điều chỉnh, người, thời điểm}]]
    $invBalancingMap = $balancings
        ->map(
            fn($rows) => $rows
                ->map(
                    fn($row) => [
                        'balancing_amount' => (float) $row->balancing_amount,
                        'balancing_by' => $row->balancing_by,
                        'balancing_at' => \Carbon\Carbon::parse($row->balancing_at)->format('d/m/Y H:i'),
                    ],
                )
                ->values(),
        )
        ->toArray();
@endphp

@section('mainContent')
    @include('pages.inventory.StandardInventory.dataTable')
@endsection

@section('model')
    @include('pages.inventory.StandardInventory.balancing')
    @include('pages.inventory.StandardInventory.balancingHistory')
    @include('pages.inventory.StandardInventory.internalExpiry')
    @include('pages.inventory.StandardInventory.weightRemarkModal')
@endsection
