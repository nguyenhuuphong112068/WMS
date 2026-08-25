<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật Kiểm Nghiệm Viên</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.materData.analyst.update') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="id" id="edit_id" value="{{ old('id') }}">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Tên Kiểm Nghiệm Viên</label>
                        <input type="text" name="name" id="edit_name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ví dụ: Nguyễn Văn A..." required>
                        @error('name', 'updateErrors')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
