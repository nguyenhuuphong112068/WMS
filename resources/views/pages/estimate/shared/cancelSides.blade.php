{{--
| Người + thời điểm xác nhận huỷ của hai bên (phòng đề nghị / bộ phận mua hàng) của một mục dự trù.
| Dùng khi mục đang chờ xác nhận huỷ (cancelState) và khi đã huỷ xong (itemStatus). Biến vào: $item
--}}
@php
    $csFmt = fn($at) => \Carbon\Carbon::parse($at)->format('d/m/Y H:i');
@endphp
@if (($item->cancel_requester_at ?? null) || ($item->cancel_purchasing_at ?? null))
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
@endif

@once
    <style>
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
