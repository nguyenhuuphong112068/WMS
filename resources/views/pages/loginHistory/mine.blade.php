@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @php
        use App\Support\LoginHistory;
        use Illuminate\Support\Carbon;
    @endphp
    @include('pages.loginHistory._style')

    <div class="content-wrapper lh-page">
        @if ($failed30 > 0)
            <div class="alert alert-warning" style="border-radius: var(--border-radius-lg, 12px)"><i class="fas fa-exclamation-triangle"></i>
                Có <b>{{ $failed30 }}</b> lần đăng nhập sai mật khẩu vào tài khoản <span class="lh-mono font-weight-bold">{{ session('user')['userName'] }}</span> trong 30 ngày qua.
                Nếu không phải bạn, hãy đổi mật khẩu và báo quản trị hệ thống.</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr><th>Thời điểm</th><th>Kết quả</th><th>Địa chỉ IP</th><th>Trình duyệt / HĐH</th><th>Kết thúc phiên</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="{{ (int) $log->id === (int) $currentId ? 'lh-current' : '' }}">
                                    <td class="text-nowrap">{{ Carbon::parse($log->login_at)->format('d/m/Y H:i:s') }}
                                        @if ((int) $log->id === (int) $currentId)<div><span class="badge badge-primary">Phiên hiện tại</span></div>@endif
                                    </td>
                                    <td>
                                        <span class="badge {{ LoginHistory::STATUSES[$log->status]['badge'] ?? 'badge-secondary' }}">{{ LoginHistory::STATUSES[$log->status]['label'] ?? $log->status }}</span>
                                        @if ($log->reason)<div class="lh-sub">{{ $log->reason }}</div>@endif
                                    </td>
                                    <td class="lh-mono">{{ $log->ip_address }}</td>
                                    <td>{{ $log->browser ?? '—' }}<div class="lh-sub">{{ $log->platform }}</div></td>
                                    <td>@include('pages.loginHistory._session', ['log' => $log])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-key fa-2x mb-2 d-block"></i>Chưa có dữ liệu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
@endsection
