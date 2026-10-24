{{-- Tiêu đề đã dùng của phòng - gắn vào ô Tiêu Đề bằng list="{{ $id }}" autocomplete="off". Biến vào: $id, $titles (App\Support\TitleSuggestions) --}}
<datalist id="{{ $id }}">
    @foreach ($titles ?? [] as $title)
        <option value="{{ $title }}"></option>
    @endforeach
</datalist>
