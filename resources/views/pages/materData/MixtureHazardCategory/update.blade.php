@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Cập Nhật {{ $mdTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Phần Bảng B <span class="text-danger">*</span></label>
                        <select name="hazard_group"
                            class="form-control {{ $bag->has('hazard_group') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn phần --</option>
                            @foreach ($groups as $code => $label)
                                <option value="{{ $code }}" {{ old('hazard_group') === $code ? 'selected' : '' }}>
                                    {{ $code }} - {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('hazard_group'))
                            <span class="md-error">{{ $bag->first('hazard_group') }}</span>
                        @endif
                        <small class="text-muted d-block mt-1">Đổi phần thì STT sẽ được xếp lại xuống cuối phần mới.</small>
                    </div>

                    <div class="form-group">
                        <label>Nhóm Phân Loại <span class="text-danger">*</span></label>
                        <textarea name="name" rows="4" maxlength="1000"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}">{{ old('name') }}</textarea>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                        <small class="text-muted d-block mt-1">Xuống dòng để tách các gạch đầu dòng.</small>
                    </div>

                    <div class="form-group">
                        <label>Ngưỡng Khối Lượng Tồn Trữ Lớn Nhất Tại Một Thời Điểm (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="threshold_kg" step="0.001" min="0"
                            class="form-control {{ $bag->has('threshold_kg') ? 'is-invalid' : '' }}"
                            value="{{ old('threshold_kg') }}">
                        @if ($bag->has('threshold_kg'))
                            <span class="md-error">{{ $bag->first('threshold_kg') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Cách Tính Ngưỡng</label>
                        <select name="threshold_basis"
                            class="form-control {{ $bag->has('threshold_basis') ? 'is-invalid' : '' }}">
                            <option value="" {{ old('threshold_basis') ? '' : 'selected' }}>Mặc định (theo tổng khối lượng)</option>
                            <option value="net" {{ old('threshold_basis') === 'net' ? 'selected' : '' }}>Theo khối lượng tịnh (net)</option>
                        </select>
                        @if ($bag->has('threshold_basis'))
                            <span class="md-error">{{ $bag->first('threshold_basis') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Sau khi sửa, bản ghi quay về trạng thái <b>Chờ duyệt</b> và cần được duyệt lại.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
