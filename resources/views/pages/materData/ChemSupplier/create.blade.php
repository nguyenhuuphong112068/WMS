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
                        <label>Tên Nhà Cung Cấp <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="255"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}"
                            value="{{ old('name') }}" placeholder="Nhập tên đầy đủ của nhà cung cấp" required>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Địa Chỉ</label>
                        <input type="text" name="address" maxlength="500"
                            class="form-control {{ $bag->has('address') ? 'is-invalid' : '' }}"
                            value="{{ old('address') }}" placeholder="Nhập địa chỉ">
                        @if ($bag->has('address'))
                            <span class="md-error">{{ $bag->first('address') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Mã Số Thuế</label>
                        <input type="text" name="tax_no" maxlength="100"
                            class="form-control {{ $bag->has('tax_no') ? 'is-invalid' : '' }}"
                            value="{{ old('tax_no') }}" placeholder="Nhập mã số thuế">
                        @if ($bag->has('tax_no'))
                            <span class="md-error">{{ $bag->first('tax_no') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="3" maxlength="1000"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Nhập ghi chú (nếu có)">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
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
