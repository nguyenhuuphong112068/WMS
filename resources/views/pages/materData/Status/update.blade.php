<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập Nhật Trạng Thái</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.status.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="update_name">Tên Trạng Thái <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="update_name" class="form-control @error('name', 'updateErrors') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name', 'updateErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
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
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
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
