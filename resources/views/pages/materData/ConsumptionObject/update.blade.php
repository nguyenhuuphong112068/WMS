<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập Nhật Đối Tượng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.consumptionObject.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id" value="{{ old('id') }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="update_type">Loại Đối Tượng <span class="text-danger">*</span></label>
                            <select name="type" id="update_type" required
                                class="form-control @error('type', 'updateErrors') is-invalid @enderror">
                                <option value="">-- Chọn loại --</option>
                                @foreach ($types as $typeCode => $typeLabel)
                                    <option value="{{ $typeCode }}" {{ old('type') === $typeCode ? 'selected' : '' }}>
                                        {{ $typeLabel }}</option>
                                @endforeach
                            </select>
                            @error('type', 'updateErrors')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-8">
                            <label for="update_code">Mã Đối Tượng <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="update_code" maxlength="50"
                                class="form-control @error('code', 'updateErrors') is-invalid @enderror"
                                value="{{ old('code') }}" required>
                            @error('code', 'updateErrors')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 d-none" id="update_cal_lock">
                            <small class="form-text text-muted mt-n2 mb-2">
                                <i class="fas fa-link mr-1"></i>Đối tượng đồng bộ từ CAL: không sửa được loại và mã.
                            </small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="update_name">Tên Đối Tượng <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="update_name" maxlength="255"
                            class="form-control @error('name', 'updateErrors') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="update_location">Vị Trí</label>
                        <input type="text" name="location" id="update_location" maxlength="255"
                            class="form-control @error('location', 'updateErrors') is-invalid @enderror"
                            value="{{ old('location') }}">
                        @error('location', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Tần Suất <span class="text-danger">*</span></label>
                        <div class="row obj-freq-grid">
                            @foreach ($frequencies as $code => $label)
                                <div class="col-6 col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" name="frequency[]"
                                            id="update_freq_{{ $loop->index }}" value="{{ $code }}"
                                            {{ in_array($code, (array) old('frequency', [])) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="update_freq_{{ $loop->index }}"
                                            title="{{ $code }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($errors->updateErrors->has('frequency') || $errors->updateErrors->has('frequency.*'))
                            <div class="text-danger small mt-1">
                                {{ $errors->updateErrors->first('frequency') ?: $errors->updateErrors->first('frequency.*') }}
                            </div>
                        @endif
                    </div>
                    <div class="form-group mb-0">
                        <label for="update_change_reason">Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                        <textarea name="change_reason" id="update_change_reason" rows="2" maxlength="500" required
                            class="form-control @error('change_reason', 'updateErrors') is-invalid @enderror"
                            placeholder="Nêu rõ lý do sửa bản ghi này">{{ old('change_reason') }}</textarea>
                        @error('change_reason', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
