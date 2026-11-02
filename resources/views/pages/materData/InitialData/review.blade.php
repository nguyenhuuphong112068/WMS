@php
    $reviewErrors = $errors->getBag('reviewErrors');
@endphp

<div class="modal fade md-modal" id="reviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Xử Lý File Khởi Tạo Dữ Liệu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.materData.initialData.review') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Trạng Thái</label>
                        <select name="review_status" class="form-control" required>
                            @foreach (\App\Http\Controllers\Pages\MaterData\InitialDataController::REVIEW_STATUSES as $key => $status)
                                <option value="{{ $key }}" @selected(old('review_status') === $key)>{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Ghi Chú</label>
                        <textarea name="review_note" class="form-control" rows="3" maxlength="1000"
                            placeholder="Trả lại thì ghi rõ lý do để phòng ban sửa, VD: sheet 28 thiếu đơn vị tính">{{ old('review_note') }}</textarea>
                        @if ($reviewErrors->has('review_note'))
                            <small class="text-danger">{{ $reviewErrors->first('review_note') }}</small>
                        @endif
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

@if ($reviewErrors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#reviewModal').modal('show');
        });
    </script>
@endif
