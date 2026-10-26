@extends ('layout.master')

@php
    /*
    |--------------------------------------------------------------------------
    | TỒN - TỒN KHO CHẤT CHUẨN
    |--------------------------------------------------------------------------
    | Khai báo tại đây để dataTable và các modal cùng đọc một nguồn.
    | Màn hình chỉ đọc phần tồn, chỉ ghi dữ liệu qua nút Cân Đối, Hạn Nội Bộ và Cách ly.
    | Hai tab Đánh giá hạn dùng CTC / Kế hoạch đánh giá dùng view của inventory/StandardStability.
    */

    /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5, 12.0000 -> 12 */
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');

    /** Ngày hiển thị d/m/Y, trống thì gạch ngang. */
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /*
    | Class cho badge .inv-mv (bấm mở modal "Chi tiết con số"): 0 -> mờ, cột trừ tồn
    | (Huỷ / Chuyển) ép đỏ, Sử Dụng ép xanh dương, còn lại tô theo dấu - như Tồn Kho Hoá Chất.
    */
    $mvCls = function ($value, $tone = null) {
        if ((float) $value == 0.0) {
            return 'inv-mv is-muted';
        }
        if ($tone === 'out') {
            return 'inv-mv is-out';
        }
        if ($tone === 'in') {
            return 'inv-mv is-in';
        }
        if ($tone === 'use') {
            return 'inv-mv is-use';
        }
        return 'inv-mv ' . ($value > 0 ? 'is-in' : 'is-out');
    };

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

    /** Ống cần kiểm nghiệm lại định kỳ (retest) - expired_date là hạn retest do NSX công bố. */
    $invIsRetest = fn($row) => ($row->expiry_type ?? null) === 'retest';

    /** Loại hạn dùng cho phép bấm badge cập nhật ngay trên bảng tồn. */
    $invCanExpiryUpdate = fn($row) => $invIsRetest($row) || $invIsCheckOnline($row);

    // Số lần đã cập nhật hạn dùng của mỗi ống, cho badge số nhỏ trên badge hạn dùng
    $invExpiryUpdateCount = collect($expiryUpdates)->map(fn($rows) => count($rows))->toArray();

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

    /*
    | TAB ĐÁNH GIÁ HẠN DÙNG CTC: biến của danh sách phiếu (StandardStabilityController::tabData)
    | kèm các hàm hiển thị, gom một mảng để @include tab và modal Lập / Sửa phiếu - tên biến
    | trong các view đó ($datas, $imports...) không đụng biến của bảng tồn.
    */
    $ssaView = null;

    if ($stability) {
        $ssaShortByCode = collect($stability['groups'])->mapWithKeys(fn($group) => [$group['code'] => $group['short']])->all();

        $ssaView = $stability + [
            'ssaRoute' => 'pages.inventory.standardStability.',
            'ssaLabel' => 'phiếu đánh giá hạn dùng',
            'ssaIcon' => 'fas fa-clipboard-list',
            'ssaDate' => $invDate,
            /** Trạng thái phiếu -> lớp CSS của thẻ .ssa-badge. */
            'ssaStatusClass' => fn($status) => match ($status) {
                'Đang Đánh Giá' => 'running',
                'Hoàn Thành' => 'done',
                'Dừng Đánh Giá' => 'stopped',
                'Huỷ' => 'cancelled',
                default => 'initial',
            },
            'ssaGroupName' => fn($code) => $ssaShortByCode[$code] ?? ($code ?: '—'),
            // Số phiếu theo từng trạng thái, dùng cho các nút lọc nhanh
            'ssaStatusCounts' => collect($stability['statuses'])
                ->mapWithKeys(fn($status) => [$status => $stability['datas']->where('status', $status)->count()])
                ->all(),
        ];
    }
@endphp

@section('mainContent')
    @include('pages.inventory.StandardInventory.dataTable')
@endsection

@section('model')
    @include('pages.inventory.StandardInventory.balancing')
    @include('pages.inventory.StandardInventory.quarantine')
    @include('pages.inventory.StandardInventory.balancingHistory')
    @include('pages.inventory.StandardInventory.internalExpiry')
    @include('pages.inventory.StandardInventory.weightRemarkModal')
    @include('pages.inventory.StandardInventory.tareWeightModal')
    @include('pages.inventory.StandardInventory.expiryUpdate')
    @include('pages.inventory.shared.movementDetail', [
        'movementUrl' => route('pages.inventory.standardInventory.movements'),
        'period' => $period,
    ])
    @include('pages.shared.attachmentListModal')
    @if ($ssaView)
        @if (user_can('stability_standard_create'))
            @include('pages.inventory.StandardStability.create', $ssaView)
        @endif
        @if (user_can('stability_standard_update'))
            @include('pages.inventory.StandardStability.update', $ssaView)
        @endif
    @endif
@endsection
