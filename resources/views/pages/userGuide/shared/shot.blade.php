{{--
| Ảnh chụp màn hình thật minh hoạ một bước.
| Biến vào:
| - $src     : đường dẫn trong public/, ví dụ 'img/userGuide/general/login.png'
| - $caption : chú thích dưới ảnh (cũng làm alt)
| - $width   : (tuỳ chọn) bề rộng tối đa khi hiển thị, px - dùng cho ảnh hẹp như form đăng nhập
| Đặt nhiều ảnh cạnh nhau: bọc các @include trong <div class="ug-shot-row">.
--}}
<figure class="ug-shot">
    <img src="{{ asset($src) }}" alt="{{ $caption }}"
        @isset($width) style="max-width: min(100%, {{ $width }}px)" @endisset>
    <figcaption>{{ $caption }}</figcaption>
</figure>
