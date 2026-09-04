<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm Phòng Ban Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.department.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="company_id">Công Ty <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-control @error('company_id', 'createErrors') is-invalid @enderror" required>
                            <option value="">-- Chọn công ty --</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }} ({{ $company->short_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('company_id', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="shortName">Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" name="shortName" class="form-control @error('shortName', 'createErrors') is-invalid @enderror" value="{{ old('shortName') }}" required>
                        @error('shortName', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Tên Phòng Ban <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name', 'createErrors') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name', 'createErrors')
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

@if($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
