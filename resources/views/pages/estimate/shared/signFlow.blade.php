{{--
| Thanh theo dõi trình ký 2 bước của một phiếu dự trù.
|
| Biến vào:
| - $row       : bản ghi estimate_lists (cần app_status, reject_step, *_signed_by, *_signed_at)
| - $signSteps : config('estimate.sign_steps')
|
| Trạng thái từng bước:
| - is-done     : đã ký, hiện tên người ký + ngày ký
| - is-current  : đang chờ người có quyền ký bước đó
| - is-rejected : bị từ chối ngay tại bước này
| - (mặc định)  : chưa tới lượt
--}}
<div class="est-flow">
    @foreach ($signSteps as $stepKey => $step)
        @php
            $signedBy = $row->{$step['signed_by']} ?? null;
            $signedAt = $row->{$step['signed_at']} ?? null;

            $state = '';
            $note = 'Chưa tới bước này';

            if ($signedAt) {
                $state = 'is-done';
                $note = $signedBy . ' - ' . \Carbon\Carbon::parse($signedAt)->format('d/m/Y H:i');
            } elseif ($row->app_status === 'rejected' && $row->reject_step === $stepKey) {
                $state = 'is-rejected';
                $note = 'Từ chối bởi ' . ($row->rejected_by ?: 'NA');
            } elseif ($row->app_status === $step['from']) {
                $state = 'is-current';
                $note = 'Đang chờ ký';
            } elseif ($row->app_status === 'draft') {
                $note = 'Chưa trình ký';
            }
        @endphp

        @if (! $loop->first)
            <span class="sep"><i class="fas fa-angle-right"></i></span>
        @endif

        <span class="est-step {{ $state }}" title="{{ $step['label'] }}: {{ $note }}">
            <span class="no">
                @if ($state === 'is-done')
                    <i class="fas fa-check"></i>
                @elseif ($state === 'is-rejected')
                    <i class="fas fa-times"></i>
                @else
                    {{ $step['no'] }}
                @endif
            </span>
            <span class="txt">
                <b>{{ $step['label'] }}</b>
                <span>{{ $note }}</span>
            </span>
        </span>
    @endforeach
</div>
