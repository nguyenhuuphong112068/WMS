@php
    $bag = $errors->getBag('itemUpdateErrors');
@endphp

<div class="modal fade md-modal" id="itemUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa Mốc Đánh Giá</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'updateItem') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Mốc Thời Gian (tháng) <span class="text-danger">*</span></label>
                            <input type="number" name="timepoint" min="0" max="127" step="1"
                                class="form-control {{ $bag->has('timepoint') ? 'is-invalid' : '' }}"
                                value="{{ old('timepoint') }}" required>
                            @if ($bag->has('timepoint'))
                                <span class="md-error">{{ $bag->first('timepoint') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-5">
                            <label>Tên Mốc Đánh Giá <span class="text-danger">*</span></label>
                            <input type="text" name="name" maxlength="100"
                                class="form-control {{ $bag->has('name') ? 'is-invalid' : '' }}"
                                value="{{ old('name') }}" required>
                            @if ($bag->has('name'))
                                <span class="md-error">{{ $bag->first('name') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Ngày Đến Hạn</label>
                            <input type="date" name="due_date"
                                class="form-control {{ $bag->has('due_date') ? 'is-invalid' : '' }}"
                                value="{{ old('due_date') }}">
                            @if ($bag->has('due_date'))
                                <span class="md-error">{{ $bag->first('due_date') }}</span>
                            @endif
                            <small class="md-sub">Để trống thì tính lại từ ngày bắt đầu + số tháng của mốc.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Chỉ Tiêu Kiểm</label>
                        <select name="testings[]" multiple
                            class="form-control ssa-select ssa-item-crit {{ $bag->has('testings') ? 'is-invalid' : '' }}">
                            @foreach ($criterias as $criteria)
                                <option value="{{ $criteria }}"
                                    {{ in_array($criteria, (array) old('testings', [])) ? 'selected' : '' }}>
                                    {{ $criteria }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('testings'))
                            <span class="md-error">{{ $bag->first('testings') }}</span>
                        @endif
                        <small class="md-sub">Chọn từ <b>Dữ Liệu Gốc → Chỉ Tiêu Kiểm</b>, tối đa
                            {{ $maxTestings }} chỉ tiêu cho một mốc.</small>
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
                        Mốc <b>đã có kết quả</b> thì không sửa ở đây được nữa - dùng nút <b>Ghi kết quả</b> để
                        chỉnh lại phần kết quả đánh giá.
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
        | JS dùng chung của .btn-md-edit đổ dữ liệu theo đúng tên ô, nhưng ô chọn nhiều
        | có name="testings[]" nên không khớp - đổ riêng ở đây từ mảng data-row.testings.
        */
        $(document).on('click', '.btn-md-edit[data-modal="#itemUpdateModal"]', function() {
            var testings = ($(this).data('row') || {}).testings || [];

            $('#itemUpdateModal').find('.ssa-item-crit').val(testings).trigger('change');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#itemUpdateModal').modal('show');
        });
    </script>
@endif
