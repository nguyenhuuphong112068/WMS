{{--
| Liên kết từ một mục về sơ đồ đường đi chung, đồng thời làm nổi đúng hàng của sơ đồ.
| Biến vào:
| - $lane  : khoá hàng trên sơ đồ (data-lane), ví dụ 'noi-bo'
| - $no    : số đường đi hiện trên sơ đồ (1-4), quyết định màu
| - $label : tên đường đi
| - $map   : (tuỳ chọn) id mục sơ đồ, mặc định 'so-do-chung'
--}}
<a class="ug-maplink k{{ $no }}" href="#{{ $map ?? 'so-do-chung' }}" data-ug-lane="{{ $lane }}">
    <i class="fas fa-project-diagram"></i>
    <span>Trên sơ đồ chung:</span>
    <span class="ug-maplink-no">{{ $no }}</span>
    <b>{{ $label }}</b>
</a>
