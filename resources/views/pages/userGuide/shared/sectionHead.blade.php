{{--
| Đầu một mục hướng dẫn: icon + tên màn hình + đường dẫn trên menu + các nhãn đặc điểm
| + nút "Mở màn hình" (chỉ hiện khi người đang xem có quyền vào màn hình đó).
|
| Biến vào:
| - $icon   : icon FontAwesome, dùng đúng icon của màn hình thật
| - $title  : tên màn hình
| - $path   : (tuỳ chọn) đường đi trên leftNAV, ví dụ 'Dữ Liệu Gốc › Đơn Vị Tính'
| - $route  : (tuỳ chọn) tên route của màn hình để mở nhanh
| - $params : (tuỳ chọn) tham số route, ví dụ ['tab' => 'prepare'] để mở thẳng một tab
| - $perm   : (tuỳ chọn) quyền xem màn hình; 'Admin' = chỉ tài khoản Admin
| - $chips  : (tuỳ chọn) [['text', 'class'], ...] class là '' | 'is-warn' | 'is-danger' | 'is-success'
--}}
@php
    $ugCanOpen = false;
    if (!empty($route) && \Illuminate\Support\Facades\Route::has($route)) {
        $ugCanOpen = match ($perm ?? null) {
            null => true,
            'Admin' => user_has_any_role(session('user')['userId'] ?? 0, ['Admin']),
            default => user_can($perm),
        };
    }
@endphp
<div class="ug-section-head">
    <div class="ug-section-icon"><i class="{{ $icon }}"></i></div>
    <div>
        <h2>{{ $title }}</h2>
        @if (!empty($path))
            <div class="ug-section-path"><i class="fas fa-bars mr-1"></i>{{ $path }}</div>
        @endif
    </div>
    <div class="ug-section-tools">
        @foreach ($chips ?? [] as $chip)
            <span class="ug-chip {{ $chip[1] ?? '' }}">{{ $chip[0] }}</span>
        @endforeach
        @if ($ugCanOpen)
            <a href="{{ route($route, $params ?? []) }}" class="btn btn-sm btn-outline-primary ug-open" target="_blank">
                <i class="fas fa-external-link-alt mr-1"></i> Mở màn hình
            </a>
        @endif
    </div>
</div>
