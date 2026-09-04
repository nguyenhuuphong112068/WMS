<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm Công Ty Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.company.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="short_name">Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" name="short_name" class="form-control @error('short_name', 'createErrors') is-invalid @enderror" value="{{ old('short_name') }}" required>
                        @error('short_name', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Tên Công Ty <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name', 'createErrors') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name', 'createErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <p class="text-muted mb-0" style="font-size: .85rem">
                        Mã công ty được cấp tự động (CT00001, CT00002...).
                    </p>
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
