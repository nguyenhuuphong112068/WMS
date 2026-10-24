{{--
    Ngày hẹn đáp ứng theo từng tháng dự trù: mục có số lượng của m tháng thì có m ngày hẹn.
    $editable = true: bộ phận mua hàng sửa trên phiếu đã duyệt; false: chỉ xem.
--}}
@php
    $promisedDaysLeft = function ($date) {
        if (! $date) {
            return '';
        }
        $diff = \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($date)->startOfDay(), false);
        if ($diff > 0) {
            return "<span class='text-success small'>Còn {$diff} ngày</span>";
        }

        return $diff == 0
            ? "<span class='text-warning small'>Hôm nay</span>"
            : "<span class='text-danger small'>Quá hạn " . abs($diff) . ' ngày</span>';
    };
@endphp
<div class="d-flex align-items-start promised-date-cell">
    <div class="flex-grow-1">
        @if ($editable)
            @csrf
        @endif
        @forelse ($item->amounts as $amount)
            <div class="promised-date-row {{ $loop->last ? '' : 'mb-2' }}">
                <div class="small text-muted">Tháng {{ \Carbon\Carbon::parse($amount->for_month_year)->format('m/Y') }}</div>
                @if ($editable)
                    <input type="date" class="form-control form-control-sm input-promised-date"
                        value="{{ $amount->promised_date ? \Carbon\Carbon::parse($amount->promised_date)->format('Y-m-d') : '' }}"
                        data-item-id="{{ $item->id }}" data-amount-id="{{ $amount->id }}"
                        data-route="{{ route($estRoute . 'updatePromisedDate') }}">
                @else
                    <div class="md-sub">{{ $amount->promised_date ? \Carbon\Carbon::parse($amount->promised_date)->format('d/m/Y') : 'Chưa có' }}</div>
                @endif
                <div class="promised-date-days-left">{!! $promisedDaysLeft($amount->promised_date) !!}</div>
            </div>
        @empty
            <span class="md-empty">—</span>
        @endforelse
    </div>
    <button type="button" class="btn btn-sm btn-link text-info p-1 ml-1 btn-promised-date-history position-relative" data-item-id="{{ $item->id }}" data-route="{{ route($estRoute . 'getPromisedDateHistory', $item->id) }}" title="Lịch sử ngày hẹn">
        <i class="fas fa-history"></i>
        @if ($item->history_count > 0)
            <span class="badge badge-danger badge-pill position-absolute promised-date-history-badge" style="top: -5px; right: -5px; font-size: 0.6rem; padding: 2px 4px;">{{ $item->history_count }}</span>
        @endif
    </button>
</div>
