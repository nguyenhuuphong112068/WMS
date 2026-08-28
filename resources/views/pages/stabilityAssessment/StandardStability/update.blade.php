@php
    $bag = $errors->getBag('updateErrors');
@endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa Phiếu Đánh Giá Hạn Dùng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Ngày Bắt Đầu Đánh Giá <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                class="form-control {{ $bag->has('start_date') ? 'is-invalid' : '' }}"
                                value="{{ old('start_date') }}" required>
                            @if ($bag->has('start_date'))
                                <span class="md-error">{{ $bag->first('start_date') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label>Chu Kỳ Đánh Giá (tháng) <span class="text-danger">*</span></label>
                            <input type="number" name="assessment_period" min="1" max="60" step="1"
                                class="form-control {{ $bag->has('assessment_period') ? 'is-invalid' : '' }}"
                                value="{{ old('assessment_period') }}" required>
                            @if ($bag->has('assessment_period'))
                                <span class="md-error">{{ $bag->first('assessment_period') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="255"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Không đổi được ống chuẩn của phiếu - muốn đánh giá ống khác thì lập phiếu khác. Đổi
                        <b>ngày bắt đầu</b> thì các mốc <b>chưa có kết quả</b> được dời ngày đến hạn theo mốc mới,
                        mốc đã đánh giá giữ nguyên ngày cũ.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
