{{-- Cột "Kết thúc phiên": thời lượng + cách kết thúc. Biến: $log --}}
@php
    $isOpen = $log->status === 'success' && ! $log->logout_at && $log->last_seen_at
        && \Illuminate\Support\Carbon::parse($log->last_seen_at)->gte(now()->subMinutes(\App\Support\LoginHistory::idleMinutes()));
    $end      = $log->logout_at ?? $log->last_seen_at;
    $minutes  = $log->status === 'success' && $end ? (int) \Illuminate\Support\Carbon::parse($log->login_at)->diffInMinutes(\Illuminate\Support\Carbon::parse($end)) : null;
    $duration = $minutes === null ? null : ($minutes >= 60 ? intdiv($minutes, 60) . ' giờ ' . ($minutes % 60) . ' phút' : ($minutes < 1 ? 'dưới 1 phút' : $minutes . ' phút'));
@endphp
@if ($log->status !== 'success')
    <span class="text-muted">—</span>
@elseif ($isOpen)
    <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 7px; vertical-align: middle"></i> Đang mở</span>
    <div class="lh-sub">{{ $duration }} · thao tác cuối {{ \Illuminate\Support\Carbon::parse($log->last_seen_at)->format('H:i') }}</div>
@elseif ($log->logout_at)
    {{ \Illuminate\Support\Carbon::parse($log->logout_at)->format('d/m H:i') }}
    <div class="lh-sub">{{ \App\Support\LoginHistory::LOGOUT_TYPES[$log->logout_type] ?? '' }} · {{ $duration }}</div>
@else
    <span class="text-muted">Hết phiên / đóng trình duyệt</span>
    <div class="lh-sub">thao tác cuối {{ $log->last_seen_at ? \Illuminate\Support\Carbon::parse($log->last_seen_at)->format('d/m H:i') : '—' }}@if ($duration) · {{ $duration }}@endif</div>
@endif
