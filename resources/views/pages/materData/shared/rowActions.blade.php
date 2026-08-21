{{--
| Cụm nút thao tác dùng chung: Sửa - Duyệt - Từ chối - Khoá/Mở khoá.
| Biến vào:
| - $prefix   : tiền tố tên route, ví dụ 'pages.materData.chemName.'
| - $row      : bản ghi đang hiển thị
| - $label    : tên chức năng viết thường, ví dụ 'tên hoá chất'
| - $title    : nội dung nhận diện dòng khi hỏi xác nhận, ví dụ $row->name
| - $editData : mảng dữ liệu đổ vào modal cập nhật (khớp theo name của từng ô nhập)
--}}
<div class="md-actions">
    <button type="button" class="btn btn-sm btn-warning btn-md-edit" title="Sửa"
        data-row="{{ json_encode($editData) }}">
        <i class="fas fa-edit"></i>
    </button>

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
