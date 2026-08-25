<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Tổ Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.materData.group.store') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Phòng Ban</label>
                        <select name="department_id" class="form-control" required>
                            <option value="">-- Chọn phòng ban --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ (old('department_id') == $dept->id || (session('user')['selected_department_id'] ?? null) == $dept->id) ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->shortName }})
                                </option>
                            @endforeach
                        </select>
                        @error('department_id', 'createErrors')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="required">Tên Tổ</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ví dụ: Tổ Hoá Lý 1, Tổ Vi Sinh..." required autofocus>
                        @error('name', 'createErrors')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
