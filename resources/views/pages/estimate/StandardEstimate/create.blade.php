@php
    $bag = $errors->getBag('createErrors');

    $estMonthNow = (int) old('month', now()->month);
    $estYearNow = (int) old('year', now()->year);
    $estPreviewKey = $estYearNow . '-' . $estMonthNow;
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $estIcon }}"></i> Lập Phiếu Dự Trù Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tháng Dự Trù <span class="text-danger">*</span></label>
                            <select name="month"
                                class="form-control est-period-part {{ $bag->has('month') ? 'is-invalid' : '' }}"
                                required>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $estMonthNow == $m ? 'selected' : '' }}>
                                        Tháng {{ $m }}
                                    </option>
                                @endfor
                            </select>
                            @if ($bag->has('month'))
                                <span class="md-error">{{ $bag->first('month') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Năm <span class="text-danger">*</span></label>
                            <input type="number" name="year" min="2020" max="2100"
                                class="form-control est-period-part {{ $bag->has('year') ? 'is-invalid' : '' }}"
                                value="{{ $estYearNow }}" required>
                            @if ($bag->has('year'))
                                <span class="md-error">{{ $bag->first('year') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Phiếu</label>
                            <input type="text" class="form-control est-readonly est-code-preview" readonly
                                data-codes="{{ json_encode($nextCodes) }}" data-placeholder="Chọn kỳ dự trù"
                                value="{{ $nextCodes[$estPreviewKey] ?? 'Chọn kỳ dự trù' }}">
                            <small class="md-sub">Sinh tự động khi lưu, tiền tố DTC.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Dự trù chất chuẩn phục vụ kiểm nghiệm quý 4">{{ old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Phiếu được lập cho phòng ban <b>{{ session('user')['selected_department'] }}</b>, mỗi kỳ chỉ có
                        một phiếu dự trù chất chuẩn. Lưu xong hệ thống mở thẳng trang chi tiết để khai các chất chuẩn
                        cần dự trù.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Mã phiếu xem trước đổi theo kỳ dự trù đang chọn */
        $(document).on('change input', '#createModal .est-period-part', function() {
            var $form = $(this).closest('form');
            var $preview = $form.find('.est-code-preview');
            var codes = $preview.data('codes') || {};
            var key = $form.find('[name="year"]').val() + '-' + parseInt($form.find('[name="month"]').val(), 10);

            $preview.val(codes[key] || $preview.data('placeholder'));
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
