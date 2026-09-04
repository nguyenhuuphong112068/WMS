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
                        <small class="text-muted d-block mt-1">Số thứ tự trong phần do hệ thống tự đánh.</small>
                    </div>

                    <div class="form-group">
                        <label>Nhóm Phân Loại <span class="text-danger">*</span></label>
                        <textarea name="name" rows="4" maxlength="1000"
                            class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Chất lỏng dễ cháy cấp 1, hoặc chất lỏng dễ cháy cấp 2/3 ở điều kiện nhiệt độ trên nhiệt độ sôi...">{{ old('name') }}</textarea>
                        @if ($bag->has('name'))
                            <span class="md-error">{{ $bag->first('name') }}</span>
                        @endif
                        <small class="text-muted d-block mt-1">Chép đúng mô tả nhóm nguy hại theo Bảng B. Xuống dòng để tách các gạch đầu dòng.</small>
                    </div>

                    <div class="form-group">
                        <label>Ngưỡng Khối Lượng Tồn Trữ Lớn Nhất Tại Một Thời Điểm (kg) <span class="text-danger">*</span></label>
                        <input type="number" name="threshold_kg" step="0.001" min="0"
                            class="form-control {{ $bag->has('threshold_kg') ? 'is-invalid' : '' }}"
                            value="{{ old('threshold_kg') }}" placeholder="Ví dụ: 50000">
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
                        <small class="text-muted d-block mt-1">Chọn "net" cho các nhóm mà Bảng B ghi chú "(net)".</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Bản ghi mới ở trạng thái <b>Chờ duyệt</b>. Sau khi duyệt, nhóm này dùng để đối chiếu
                        ngưỡng tồn trữ Bảng B cho các hỗn hợp được tick trên màn Tên Hoá Chất.
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
