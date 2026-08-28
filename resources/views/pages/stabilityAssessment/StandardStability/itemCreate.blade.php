@php
    $bag = $errors->getBag('itemCreateErrors');

    // Mốc gợi ý cho lần thêm tiếp theo: mốc lớn nhất đang có cộng thêm một chu kỳ
    $ssaNextTimepoint = $items->max('timepoint');
    $ssaNextTimepoint = $ssaNextTimepoint === null ? 0 : (int) $ssaNextTimepoint + (int) $list->assessment_period;
    $ssaNextTimepoint = min($ssaNextTimepoint, 127);
@endphp

<div class="modal fade md-modal" id="itemCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Thêm Mốc Đánh Giá</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'storeItem') }}" method="POST" class="ssa-item-form"
                data-start="{{ substr((string) $list->start_date, 0, 10) }}">
                @csrf
                <input type="hidden" name="standard_stability_assessment_list_id" value="{{ $list->id }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Mốc Thời Gian (tháng) <span class="text-danger">*</span></label>
                            <input type="number" name="timepoint" min="0" max="127" step="1"
                                class="form-control ssa-timepoint-input {{ $bag->has('timepoint') ? 'is-invalid' : '' }}"
                                value="{{ old('timepoint', $ssaNextTimepoint) }}" required>
                            @if ($bag->has('timepoint'))
                                <span class="md-error">{{ $bag->first('timepoint') }}</span>
                            @endif
                            <small class="md-sub">0 là mốc ban đầu.</small>
                        </div>

                        <div class="form-group col-md-5">
                            <label>Tên Mốc Đánh Giá <span class="text-danger">*</span></label>
                            <input type="text" name="name" maxlength="100"
                                class="form-control ssa-name-input {{ $bag->has('name') ? 'is-invalid' : '' }}"
                                value="{{ old('name') }}" placeholder="Ví dụ: 6 Tháng" required>
                            @if ($bag->has('name'))
                                <span class="md-error">{{ $bag->first('name') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Ngày Đến Hạn</label>
                            <input type="date" name="due_date"
                                class="form-control ssa-due-input {{ $bag->has('due_date') ? 'is-invalid' : '' }}"
                                value="{{ old('due_date') }}">
                            @if ($bag->has('due_date'))
                                <span class="md-error">{{ $bag->first('due_date') }}</span>
                            @endif
                            <small class="md-sub">Tự tính từ ngày bắt đầu
                                {{ \Carbon\Carbon::parse($list->start_date)->format('d/m/Y') }}, sửa lại được.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Chỉ Tiêu Kiểm</label>
                        <select name="testings[]" multiple
                            class="form-control ssa-select {{ $bag->has('testings') ? 'is-invalid' : '' }}">
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
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu mốc
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /*
        | Ngày đến hạn = ngày bắt đầu + số tháng của mốc, cộng theo kiểu "không tràn tháng"
        | đúng như addMonthsNoOverflow của Carbon bên Controller (31/01 + 1 tháng = 28/02).
        */
        function addMonths(startDate, months) {
            var parts = String(startDate).split('-');

            if (parts.length !== 3) return '';

            var day = parseInt(parts[2], 10);
            var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1 + months, 1);
            var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

            date.setDate(Math.min(day, lastDay));

            var mm = String(date.getMonth() + 1).padStart(2, '0');
            var dd = String(date.getDate()).padStart(2, '0');

            return date.getFullYear() + '-' + mm + '-' + dd;
        }

        /* Đổi mốc thời gian thì tên mốc và ngày đến hạn tự điền lại theo mốc mới */
        $(document).on('change input', '#itemCreateModal .ssa-timepoint-input', function() {
            var $form = $(this).closest('form');
            var timepoint = parseInt($(this).val(), 10);

            if (isNaN(timepoint) || timepoint < 0) return;

            $form.find('.ssa-due-input').val(addMonths($form.data('start'), timepoint));
            // Cùng cách đặt tên với form lập phiếu và nút sinh nhanh: "Ban đầu" / "6 Tháng"
            $form.find('.ssa-name-input').val(timepoint === 0 ? 'Ban đầu' : timepoint + ' Tháng');
        });

        /* Mở modal thì điền sẵn theo mốc đang gợi ý */
        $(document).on('click', '.btn-md-create[data-modal="#itemCreateModal"]', function() {
            setTimeout(function() {
                $('#itemCreateModal .ssa-timepoint-input').val({{ $ssaNextTimepoint }}).trigger('change');
            }, 0);
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#itemCreateModal').modal('show');
        });
    </script>
@endif
