{{--
| Tiến trình ký 2 bước của một đề nghị liên phòng ban Hoá Chất / Chất Chuẩn (App\Support\TransferSignFlow):
| quản lý bộ phận phòng đề nghị -> quản lý bộ phận phòng cấp phát. Phiếu bị từ chối ký (về Lưu tạm)
| thì hiện lý do. Biến vào: $tsSigns (các bước, theo step_no), $tsReq (đầu phiếu).
| Vật Tư / VPP dùng bản riêng MaterialExport/transferSignChips, VppExport/transferSignChips.
--}}
@if ($tsSigns->count())
    <div class="ts-sign-chips">
        @foreach ($tsSigns as $sign)
            @php
                $isCurrent = $tsReq->status === 'pending_sign' && (int) $tsReq->current_step === (int) $sign->step_no;
                $state = $sign->status === 'signed' ? 'is-signed' : ($sign->status === 'rejected' ? 'is-rejected' : ($isCurrent ? 'is-current' : ''));
            @endphp
            <span class="ts-sign-chip {{ $state }}"
                title="{{ $sign->signed_by ? $sign->signed_by . ' · ' . \Carbon\Carbon::parse($sign->signed_at)->format('d/m/Y H:i') : ($isCurrent ? 'Đang chờ ký' : 'Chưa tới lượt') }}{{ $sign->note ? ' · ' . $sign->note : '' }}">
                <b>{{ $sign->step_no }}</b> {{ $sign->role_names }} · {{ $sign->department_short ?: $sign->department_name }}
            </span>
        @endforeach
    </div>
@endif
@if ($tsReq->status === 'draft' && ! empty($tsReq->sign_reject_reason))
    <div class="ts-sign-rejected" title="{{ $tsReq->sign_rejected_by }}">
        <i class="fas fa-triangle-exclamation mr-1"></i>Bị từ chối: {{ $tsReq->sign_reject_reason }}
    </div>
@endif

@once
    <style>
        .ts-sign-chips {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            margin-top: 5px;
        }

        .ts-sign-chip {
            display: inline-block;
            border-radius: 999px;
            padding: 1px 9px;
            font-size: 0.7rem;
            white-space: nowrap;
            background: #F1F5F9;
            color: #64748B;
            border: 1px solid #E2E8F0;
        }

        .ts-sign-chip.is-signed {
            background: #DCFCE7;
            color: #15803D;
            border-color: #86EFAC;
        }

        .ts-sign-chip.is-current {
            background: #FEF3C7;
            color: #B45309;
            border-color: #FCD34D;
            font-weight: 700;
        }

        .ts-sign-chip.is-rejected {
            background: #FEE2E2;
            color: #B91C1C;
            border-color: #FCA5A5;
        }

        .ts-sign-rejected {
            margin-top: 5px;
            font-size: 0.72rem;
            color: var(--danger, #DC2626);
            white-space: normal;
        }
    </style>
@endonce
