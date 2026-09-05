<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập Nhật Thông Tin Phòng Ban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.department.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="update_company_id">Công Ty <span class="text-danger">*</span></label>
                        <select name="company_id" id="update_company_id" class="form-control @error('company_id', 'updateErrors') is-invalid @enderror" required>
                            <option value="">-- Chọn công ty --</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }} ({{ $company->short_name }})</option>
                            @endforeach
                        </select>
                        @error('company_id', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="update_shortName">Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" name="shortName" id="update_shortName" class="form-control @error('shortName', 'updateErrors') is-invalid @enderror" required>
                        @error('shortName', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="update_name">Tên Phòng Ban <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="update_name" class="form-control @error('name', 'updateErrors') is-invalid @enderror" required>
                        @error('name', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="is_common" id="update_is_common" class="form-check-input" value="1">
                        <label class="form-check-label" for="update_is_common">Phòng ban chung (chỉ để tạo user, không hiện trong Chuyển Bộ Phận, không tham gia nghiệp vụ)</label>
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

@if($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
