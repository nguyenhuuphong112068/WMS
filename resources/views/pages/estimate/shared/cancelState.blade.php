{{--
| Trạng thái đề nghị huỷ 2 bên của một mục dự trù vật tư (phòng đề nghị + bộ phận mua hàng).
| Chỉ in khi mục đang chờ xác nhận huỷ. Biến vào: $item
--}}
@if ((int) $item->status_id === 1 && ($item->cancel_requester_at || $item->cancel_purchasing_at))
    <div class="est-cancel-state">
        <span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Chờ xác nhận huỷ</span>
        @if ($item->cancel_reason)
            <div class="small text-danger mt-1">Lý do: {{ $item->cancel_reason }}</div>
        @endif
        @include('pages.estimate.shared.cancelSides')
    </div>
@endif

@once
    <style>
        .est-cancel-state {
            margin-bottom: 6px;
        }

    </style>
@endonce
