@extends ('layout.master')

@php
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    /*
    | Class cho badge .inv-mv (bấm mở modal "Chi tiết con số"): 0 -> mờ, cột trừ tồn
    | (Sử Dụng / Loại Bỏ) ép đỏ, còn lại tô theo dấu.
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
        return 'inv-mv ' . ($value > 0 ? 'is-in' : 'is-out');
    };

    $invToday = \Carbon\Carbon::today();
    $invPeriodLabel = \Carbon\Carbon::parse($period['from'])->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($period['to'])->format('d/m/Y');

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
            'label' => 'Hôm Nay',
            'from' => $invToday->copy()->format('Y-m-d'),
            'to' => $invToday->copy()->format('Y-m-d'),
        ],
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

    // [import_id => [{balancing_amount, balancing_by, balancing_at}]] cho modal lịch sử cân đối
    $invBalancingMap = $balancings->map(fn($rows) => $rows->map(fn($r) => [
        'balancing_amount' => (float) $r->balancing_amount,
        'balancing_by' => $r->balancing_by,
        'balancing_at' => \Carbon\Carbon::parse($r->balancing_at)->format('d/m/Y H:i'),
    ])->values());
@endphp

@section('mainContent')
    @include('pages.inventory.MaterialInventory.dataTable')
@endsection

@section('model')
    @include('pages.inventory.MaterialInventory.balancing')
    @include('pages.inventory.MaterialInventory.balancingHistory')
    @include('pages.inventory.MaterialInventory.zoneDetail')
    @include('pages.inventory.MaterialInventory.chart')
    @include('pages.inventory.shared.movementDetail', [
        'movementUrl' => route('pages.inventory.materialInventory.movements'),
        'period' => $period,
    ])
    @include('pages.inventory.MaterialInventory.stocktakeDetail')
    {{-- Modal camera cho ô quét QR của tab Kiểm Kê Định Kỳ --}}
    @include('pages.shared.cameraScan')
    @include('pages.shared.attachmentListModal')
@endsection
