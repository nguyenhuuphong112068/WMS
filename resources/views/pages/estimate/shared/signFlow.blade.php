{{--
| Thanh theo dõi trình ký của một phiếu dự trù (quy trình động).
|
| Biến vào:
| - $row   : bản ghi <loại>_estimates (cần app_status, current_step)
| - $signs : collection bước ký của phiếu (<loại>_estimate_signs), đã sắp theo step_no
|
| Trạng thái từng bước:
| - is-done     : status = signed  -> hiện tên người ký + thời điểm
| - is-rejected : status = rejected
| - is-current  : phiếu đang pending_sign và current_step trỏ vào bước này
| - (mặc định)  : chưa tới lượt
--}}
@php $signs = collect($signs ?? []); @endphp

<div class="est-flow">
    @forelse ($signs as $step)
        @php
            $who = $step->signer_full_name ?: ($step->user_name ?: ($step->role_names ?: '—'));

            $state = '';
            $note = 'Chưa tới bước này';

            if ($step->status === 'signed') {
                $state = 'is-done';
                $note = ($step->signed_by ?: $who) . ($step->signed_at ? ' - ' . \Carbon\Carbon::parse($step->signed_at)->format('d/m/Y H:i') : '');
            } elseif ($step->status === 'rejected') {
                $state = 'is-rejected';
                $note = 'Từ chối' . ($step->reject_reason ? ': ' . $step->reject_reason : '');
            } elseif ($row->app_status === 'pending_sign' && (int) $row->current_step === (int) $step->step_no) {
                $state = 'is-current';
                $note = 'Đang chờ ' . $who . ' ký';
            } elseif ($row->app_status === 'draft') {
                $note = 'Chưa trình ký';
            }
        @endphp

        @if (! $loop->first)
            <span class="sep"><i class="fas fa-angle-right"></i></span>
        @endif

        <span class="est-step {{ $state }}" title="Bước {{ $step->step_no }} - {{ $who }}: {{ $note }}">
            <span class="no">
                @if ($state === 'is-done')
                    <i class="fas fa-check"></i>
                @elseif ($state === 'is-rejected')
                    <i class="fas fa-times"></i>
                @else
                    {{ $step->step_no }}
                @endif
            </span>
            <span class="txt">
                <b>{{ $who }}</b>
                <span>{{ $note }}</span>
            </span>
        </span>
    @empty
        <span class="est-step" title="Phiếu chưa khai quy trình ký">
            <span class="no"><i class="fas fa-minus"></i></span>
            <span class="txt"><b>Chưa khai quy trình ký</b><span>Bấm Sửa để khai người ký</span></span>
        </span>
    @endforelse
</div>
