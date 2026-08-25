<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Tên Sản Phẩm Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.materData.productName.store') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Tên Sản Phẩm</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ví dụ: Paracetamol 500mg, Amoxicillin 250mg..." required autofocus>
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
