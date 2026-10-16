{{--
| Tiến trình ký của một đề nghị liên phòng ban / VPP (dưới trạng thái phiếu). Phiếu bị từ chối
| ký (về Lưu tạm) thì hiện lý do. Biến vào: $mtSigns (các bước, theo step_no), $mtReq (đầu phiếu).
--}}
@if ($mtSigns->count())
    <div class="mt-sign-chips">
        @foreach ($mtSigns as $sign)
            @php
                $isCurrent = $mtReq->status === 'pending_sign' && (int) $mtReq->current_step === (int) $sign->step_no;
                $state = $sign->status === 'signed' ? 'is-signed' : ($sign->status === 'rejected' ? 'is-rejected' : ($isCurrent ? 'is-current' : ''));
            @endphp
            <span class="mt-sign-chip {{ $state }}"
                title="{{ $sign->signed_by ? $sign->signed_by . ' · ' . \Carbon\Carbon::parse($sign->signed_at)->format('d/m/Y H:i') : ($isCurrent ? 'Đang chờ ký' : 'Chưa tới lượt') }}{{ $sign->note ? ' · ' . $sign->note : '' }}">
                <b>{{ $sign->step_no }}</b> {{ $sign->role_names }} · {{ $sign->department_short ?: $sign->department_name }}
            </span>
        @endforeach
    </div>
@endif
@if ($mtReq->status === 'draft' && ! empty($mtReq->sign_reject_reason))
    <div class="mt-sign-rejected" title="{{ $mtReq->sign_rejected_by }}">
        <i class="fas fa-triangle-exclamation mr-1"></i>Bị từ chối: {{ $mtReq->sign_reject_reason }}
    </div>
@endif

@once
    <style>
        .mt-sign-chips {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            margin-top: 5px;
        }

        .mt-sign-chip {
            display: inline-block;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.7rem;
            white-space: nowrap;
            background: #F1F5F9;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }

        .mt-sign-chip.is-signed {
            background: #DCFCE7;
            color: #15803D;
            border-color: #86EFAC;
        }

        .mt-sign-chip.is-current {
            background: #FEF3C7;
            color: #B45309;
            border-color: #FCD34D;
            font-weight: 700;
        }

        .mt-sign-chip.is-rejected {
            background: #FEE2E2;
            color: #B91C1C;
            border-color: #FCA5A5;
        }

        .mt-sign-rejected {
            margin-top: 5px;
            font-size: 0.72rem;
            color: #DC2626;
            white-space: normal;
        }
    </style>
@endonce
