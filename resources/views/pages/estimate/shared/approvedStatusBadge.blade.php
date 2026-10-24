{{--
| Badge TÌNH TRẠNG xử lý của một phiếu dự trù đã duyệt - EstimateItemCancel::approvedStatus().
| Dùng ở tab "Danh sách phiếu" (phòng đề nghị) và tab "Đơn dự trù đã được phê duyệt" (bộ phận
| mua hàng). Chưa tiếp nhận (vàng) / Đã hoàn thành 0/m (xám) / n/m (xanh dương + thanh tiến độ) /
| Hoàn thành (xanh lá) / Huỷ (đỏ).
|
| Biến vào: $apStatus = ['key', 'label', 'done', 'total'] hoặc null (phiếu chưa duyệt -> "—")
--}}
@if ($apStatus)
    @php
        $apsIcon = ['waiting' => 'fa-inbox', 'progress' => 'fa-spinner', 'completed' => 'fa-check-circle', 'cancelled' => 'fa-ban'][$apStatus['key']];
        $apsPercent = $apStatus['total'] > 0 ? round($apStatus['done'] * 100 / $apStatus['total']) : 0;
    @endphp
    <span class="ap-status is-{{ $apStatus['key'] }} {{ $apStatus['key'] === 'progress' && $apStatus['done'] === 0 ? 'is-zero' : '' }}">
        <i class="fas {{ $apsIcon }} mr-1"></i>{{ $apStatus['label'] }}
    </span>
    @if ($apStatus['key'] === 'progress' && $apStatus['total'] > 0)
        <div class="progress ap-progress" title="{{ $apStatus['done'] }}/{{ $apStatus['total'] }} mục đã giao">
            <div class="progress-bar" style="width: {{ $apsPercent }}%"></div>
        </div>
    @endif
@else
    <span class="md-empty">—</span>
@endif

@once
    <style>
        .ap-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .ap-status.is-waiting {
            color: #B45309;
            background: rgba(245, 158, 11, 0.14);
            border-color: rgba(245, 158, 11, 0.45);
        }

        .ap-status.is-progress {
            color: var(--primary-dark);
            background: var(--primary-soft);
            border-color: var(--primary-lighter);
        }

        .ap-status.is-progress.is-zero {
            color: #64748B;
            background: #F1F5F9;
            border-color: #CBD5E1;
        }

        .ap-status.is-completed {
            color: #15803D;
            background: rgba(22, 163, 74, 0.12);
            border-color: rgba(22, 163, 74, 0.4);
        }

        .ap-status.is-cancelled {
            color: #B91C1C;
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.4);
        }

        .ap-progress {
            height: 5px;
            margin: 6px auto 0;
            max-width: 140px;
            border-radius: 999px;
            background: #E2E8F0;
        }

        .ap-progress .progress-bar {
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
            border-radius: 999px;
        }
    </style>
@endonce
