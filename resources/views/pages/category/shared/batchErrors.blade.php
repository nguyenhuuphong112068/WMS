{{--
| Khung lỗi đầu modal bảng nhiều dòng: gom lỗi của bag $bag thành "Dòng N: ..." (khoá items.<i>.<trường>)
| để người dùng biết dòng nào sai. Ẩn khi chưa có lỗi; mở lại modal Thêm mới thì cbtBatch tự ẩn.
--}}
@php
    $cbtErrors = collect($bag->messages())
        ->map(fn($messages, $key) => preg_match('/^items\.(\d+)\./', $key, $m)
            ? 'Dòng ' . ($m[1] + 1) . ': ' . $messages[0]
            : $messages[0])
        ->unique()
        ->values();
@endphp
<div class="alert alert-danger cbt-errors" @if ($cbtErrors->isEmpty()) style="display:none" @endif>
    <ul>
        @foreach ($cbtErrors as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
</div>
