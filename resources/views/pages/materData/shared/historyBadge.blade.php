{{--
| Badge số lần thay đổi của một dòng dữ liệu gốc - bấm vào mở modal xem lịch sử.
| Chỉ hiện khi bản ghi đã thật sự bị đổi ít nhất một lần (lần "Thêm mới" không tính).
|
| Đặt ngay sau nút Sửa và bọc cả hai trong <span class="md-btn-wrap"> để badge nằm
| đúng góc trên bên phải của nút.
|
| Biến vào:
| - $count : số lần đã thay đổi
| - $url   : route trả về JSON lịch sử của dòng này
| - $title : nội dung nhận diện dòng, hiện ở tiêu đề modal
--}}
@if (($count ?? 0) > 0)
    <button type="button" class="md-count-badge btn-md-history"
        title="Xem {{ $count }} lần thay đổi của &quot;{{ $title }}&quot;"
        data-url="{{ $url }}"
        data-title="{{ $title }}">{{ $count }}</button>
@endif
