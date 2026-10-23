{{--
| Tiếp nhận phiếu dự trù đã duyệt (bộ phận mua hàng) - EstimateItemCancel::receive().
| Chờ tiếp nhận: nút "Tiếp nhận"; đã tiếp nhận: người + ngày tiếp nhận.
|
| Biến vào: $list (reception_status, received_by, received_at, code, id), $estRoute,
|           $raCompact (true = nút nhỏ trên dòng bảng)
--}}
@php $raCompact = $raCompact ?? false; @endphp

@if ($list->reception_status === 'waiting')
    <form action="{{ route($estRoute . 'receive') }}" method="POST" class="d-inline form-md-confirm"
        data-title="Tiếp nhận phiếu dự trù {{ $list->code }}?"
        data-text="Ghi nhận bạn là người tiếp nhận, thời điểm tiếp nhận là bây giờ.">
        @csrf
        <input type="hidden" name="id" value="{{ $list->id }}">
        <button type="submit" class="btn {{ $raCompact ? 'btn-sm' : 'ml-1' }} btn-warning" title="Tiếp nhận phiếu để xử lý mua hàng">
            <i class="fas fa-hand-holding mr-1"></i>Tiếp nhận
        </button>
    </form>
@elseif ($list->received_at)
    <span class="{{ $raCompact ? 'md-sub' : 'badge badge-success ml-1 p-2' }}" title="Người tiếp nhận">
        <i class="fas fa-user-check mr-1"></i>{{ $list->received_by }}
        · {{ \Carbon\Carbon::parse($list->received_at)->format('d/m/Y H:i') }}
    </span>
@endif
