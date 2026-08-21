{{--
| Nhãn trạng thái phê duyệt dùng chung.
| Biến vào: $row (bản ghi có cột app_status, approved_by, approved_at)
--}}
@php
    $mdLabels = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
    ];
    $mdKey = $row->app_status ?? 'pending';
@endphp

<span class="md-badge {{ $mdKey }}">{{ $mdLabels[$mdKey] ?? $mdKey }}</span>

@if ($row->approved_by)
    <div class="md-sub mt-1">
        {{ $row->approved_by }}
        @if ($row->approved_at)
            <br><small>{{ \Carbon\Carbon::parse($row->approved_at)->format('d/m/Y H:i') }}</small>
        @endif
    </div>
@endif
