{{--
| Cụm nút thao tác của nhóm Danh Mục: Sửa - Lịch sử - Duyệt - Từ chối - Khoá/Mở khoá.
| Giống nhóm Dữ Liệu Gốc nhưng có thêm badge xem lịch sử thay đổi.
|
| Biến vào:
| - $prefix       : tiền tố tên route, ví dụ 'pages.category.materialCategory.'
| - $row          : bản ghi đang hiển thị
| - $label        : tên chức năng viết thường, ví dụ 'danh mục vật tư'
| - $title        : nội dung nhận diện dòng khi hỏi xác nhận
| - $editData     : mảng dữ liệu đổ vào modal cập nhật (khớp theo name của từng ô nhập)
| - $historyCount : (tuỳ chọn) số lần đã thay đổi, mặc định 0 nếu không truyền
--}}
<div class="md-actions">
    <span class="cat-btn-wrap">
        <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
            data-row="{{ json_encode($editData) }}">
            <i class="fas fa-edit"></i>
        </button>

        {{-- Badge số lần thay đổi, nằm ở góc trên bên phải nút Sửa - chỉ hiện khi đã từng đổi --}}
        @if (($historyCount ?? 0) > 0)
            <button type="button" class="cat-count-badge btn-cat-history"
                title="Xem {{ $historyCount }} lần thay đổi của {{ $label }} này"
                data-url="{{ route($prefix . 'history', ['id' => $row->id]) }}"
                data-title="{{ $title }}">{{ $historyCount }}</button>
        @endif
    </span>

    {{-- Chỉ màn hoá chất truyền $showConvert, vì quy đổi cần tỉ trọng --}}
    @isset($showConvert)
        <button type="button" class="btn btn-sm btn-outline-info btn-cat-convert" title="Quy đổi đơn vị"
            data-id="{{ $row->id }}" data-unit="{{ $row->unit_id }}" data-title="{{ $title }}">
            <i class="fas fa-balance-scale"></i>
        </button>
    @endisset

    @if (($row->app_status ?? 'pending') !== 'approved')
        <form class="form-md-confirm d-inline" action="{{ route($prefix . 'approve') }}" method="POST"
            data-title="Duyệt {{ $label }}?"
            data-text="Sau khi duyệt, {{ $label }} &quot;{{ $title }}&quot; sẽ được dùng ở các màn hình nghiệp vụ.">
            @csrf
            <input type="hidden" name="id" value="{{ $row->id }}">
            <button type="submit" class="btn btn-sm btn-success" title="Duyệt">
                <i class="fas fa-check"></i>
            </button>
        </form>
    @endif

    @if (($row->app_status ?? 'pending') !== 'rejected')
        <form class="form-md-confirm d-inline" action="{{ route($prefix . 'reject') }}" method="POST"
            data-title="Từ chối {{ $label }}?"
            data-text="{{ ucfirst($label) }} &quot;{{ $title }}&quot; sẽ bị đánh dấu từ chối và không được dùng."
            data-danger="1">
            @csrf
            <input type="hidden" name="id" value="{{ $row->id }}">
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Từ chối">
                <i class="fas fa-times"></i>
            </button>
        </form>
    @endif

    <form class="form-md-confirm d-inline" action="{{ route($prefix . 'deActive') }}" method="POST"
        data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} {{ $label }}?"
        data-text="{{ $row->status_id == 1 ? 'Sau khi khoá' : 'Sau khi mở khoá' }}, {{ $label }} &quot;{{ $title }}&quot; {{ $row->status_id == 1 ? 'sẽ không còn xuất hiện khi chọn dữ liệu.' : 'sẽ được dùng lại bình thường.' }}"
        data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
        @csrf
        <input type="hidden" name="id" value="{{ $row->id }}">
        <button type="submit" class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
            title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
            <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
        </button>
    </form>
</div>
