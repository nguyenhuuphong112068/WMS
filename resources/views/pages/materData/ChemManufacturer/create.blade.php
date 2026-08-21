@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-group">
                        <label>Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" maxlength="100"
                            class="form-control {{ $bag->has('short_name') ? 'is-invalid' : '' }}"
                            value="{{ old('short_name') }}" placeholder="Ví dụ: MERCK" required>
                        @if ($bag->has('short_name'))
                            <span class="md-error">{{ $bag->first('short_name') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Tên Nhà Sản Xuất <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="255"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}"
                            value="{{ old('name') }}" placeholder="Nhập tên đầy đủ của nhà sản xuất" required>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Bản ghi mới ở trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng ở các màn hình nghiệp vụ.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
