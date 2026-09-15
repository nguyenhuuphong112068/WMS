<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm Đối Tượng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.consumptionObject.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="create_type">Loại Đối Tượng <span class="text-danger">*</span></label>
                            <select name="type" id="create_type" required
                                class="form-control @error('type', 'createErrors') is-invalid @enderror">
                                <option value="">-- Chọn loại --</option>
                                @foreach ($types as $typeCode => $typeLabel)
                                    <option value="{{ $typeCode }}" {{ old('type') === $typeCode ? 'selected' : '' }}>
                                        {{ $typeLabel }}</option>
                                @endforeach
                            </select>
                            @error('type', 'createErrors')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="create_code">Mã Đối Tượng <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="create_code" maxlength="50"
                                class="form-control @error('code', 'createErrors') is-invalid @enderror"
                                value="{{ old('code') }}" required>
                            @error('code', 'createErrors')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="create_frequency">Tần Suất <span class="text-danger">*</span></label>
                            <select name="frequency" id="create_frequency" required
                                class="form-control @error('frequency', 'createErrors') is-invalid @enderror">
                                <option value="">-- Chọn tần suất --</option>
                                @foreach ($frequencies as $frequencyCode => $frequencyLabel)
                                    <option value="{{ $frequencyCode }}"
                                        {{ old('frequency') === $frequencyCode ? 'selected' : '' }}>{{ $frequencyLabel }}</option>
                                @endforeach
                            </select>
                            @error('frequency', 'createErrors')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="create_name">Tên Đối Tượng <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="create_name" maxlength="255"
                            class="form-control @error('name', 'createErrors') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="create_location">Vị Trí</label>
                        <input type="text" name="location" id="create_location" maxlength="255"
                            class="form-control @error('location', 'createErrors') is-invalid @enderror"
                            value="{{ old('location') }}">
                        @error('location', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
