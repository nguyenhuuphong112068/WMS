{{--
| Trạng thái đề nghị huỷ 2 bên của một mục dự trù vật tư (phòng đề nghị + bộ phận mua hàng).
| Chỉ in khi mục đang chờ xác nhận huỷ. Biến vào: $item
--}}
@if ((int) $item->status_id === 1 && ($item->cancel_requester_at || $item->cancel_purchasing_at))
    @php
        $csFmt = fn($at) => \Carbon\Carbon::parse($at)->format('d/m/Y H:i');
    @endphp
    <div class="est-cancel-state">
        <span class="badge badge-warning"><i class="fas fa-hourglass-half mr-1"></i>Chờ xác nhận huỷ</span>
        @if ($item->cancel_reason)
            <div class="small text-danger mt-1">Lý do: {{ $item->cancel_reason }}</div>
        @endif
        <div class="est-cancel-sides">
            <div class="{{ $item->cancel_requester_at ? 'is-done' : '' }}">
                <i class="fas {{ $item->cancel_requester_at ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>Phòng đề nghị:
                {{ $item->cancel_requester_at ? $item->cancel_requester_by . ' · ' . $csFmt($item->cancel_requester_at) : 'chờ xác nhận' }}
            </div>
            <div class="{{ $item->cancel_purchasing_at ? 'is-done' : '' }}">
                <i class="fas {{ $item->cancel_purchasing_at ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>Bộ phận mua hàng:
                {{ $item->cancel_purchasing_at ? $item->cancel_purchasing_by . ' · ' . $csFmt($item->cancel_purchasing_at) : 'chờ xác nhận' }}
            </div>
        </div>
    </div>
@endif

@once
    <style>
        .est-cancel-state {
            margin-bottom: 6px;
        }

        .est-cancel-sides {
            margin-top: 4px;
            font-size: 0.76rem;
            color: #92400E;
            line-height: 1.5;
        }

        .est-cancel-sides .is-done {
            color: #16A34A;
        }
    </style>
@endonce
