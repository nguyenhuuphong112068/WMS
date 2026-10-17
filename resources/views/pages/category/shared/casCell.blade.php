{{--
| Ô "Số CAS" của bảng Danh Mục Hoá Chất Công Ty / Hoá Chất Của Phòng.
| Hỗn hợp khai nhiều số CAS (ngăn bằng dấu phẩy / chấm phẩy): giữ nguyên từng số
| trên một dòng, chỉ xuống dòng ở dấu phẩy - tránh bị ngắt "67-" / "64-1".
--}}
<td class="md-sub">
    @if ($casNo)
        @foreach (preg_split('/\s*[,;]\s*/', $casNo, -1, PREG_SPLIT_NO_EMPTY) as $cas)
            <span class="text-nowrap">{{ $cas }}{{ $loop->last ? '' : ',' }}</span>
        @endforeach
    @else
        <span class="md-empty">—</span>
    @endif
</td>
