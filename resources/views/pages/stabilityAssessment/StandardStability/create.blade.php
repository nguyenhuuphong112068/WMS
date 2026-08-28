@php
    $bag = $errors->getBag('createErrors');

    /*
    | Thông tin từng ống chuẩn cho JS xem trước ngay dưới ô chọn, để người lập phiếu
    | thấy đúng chất chuẩn / số lô / hạn dùng trước khi lưu.
    */
    $ssaImportMap = $imports
        ->mapWithKeys(
            fn($import) => [
                $import->id => [
                    'standard_name' => $import->standard_name ?: '—',
                    'category_code' => $import->category_code ?: '—',
                    'batch_no' => $import->batch_no ?: '—',
                    'imported_date' => $ssaDate($import->imported_date),
                    'expired_date' => $ssaDate($import->expired_date),
                ],
            ],
        )
        ->all();

    /*
    | CÁC MỐC ĐÁNH GIÁ KHAI NGAY TRÊN FORM.
    |
    | Form lỗi validate thì dựng lại đúng các dòng người dùng vừa gõ; mở mới thì cho
    | sẵn một dòng trống để không phải bấm "Thêm dòng" ngay từ đầu.
    */
    $ssaOldItems = array_values((array) old('items', []));

    if (! $ssaOldItems) {
        $ssaOldItems = [['timepoint' => '', 'testings' => [], 'note' => '']];
    }

    // Các thẻ <option> chỉ tiêu kiểm, dùng lại cho dòng sinh thêm bằng JS
    $ssaCriteriaOptions = collect($criterias)
        ->map(fn($name) => '<option value="' . e($name) . '">' . e($name) . '</option>')
        ->implode('');
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    {{-- Form cao nên không dùng modal-dialog-centered: căn giữa mà nội dung dài hơn màn
         hình thì phần trên bị cắt, không kéo lên xem lại được. --}}
    <div class="modal-dialog ssa-modal-wide" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $ssaIcon }}"></i> Lập Phiếu Đánh Giá Hạn Dùng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($ssaRoute . 'store') }}" method="POST" id="ssaCreateForm">
                @csrf
                <div class="modal-body">

                    <div class="form-group">
                        <label>Mã Ống Chuẩn Cần Đánh Giá <span class="text-danger">*</span></label>
                        <select name="import_id" data-info="{{ json_encode($ssaImportMap) }}"
                            class="form-control ssa-select ssa-import-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}"
                            required>
                            <option value="">-- Chọn mã ống chuẩn --</option>
                            @foreach ($imports as $import)
                                <option value="{{ $import->id }}"
                                    {{ old('import_id') == $import->id ? 'selected' : '' }}>
                                    {{ $import->code }} - {{ $import->standard_name ?: 'Chưa có tên' }}
                                    @if ($import->batch_no)
                                        (Lô {{ $import->batch_no }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('import_id'))
                            <span class="md-error">{{ $bag->first('import_id') }}</span>
                        @endif
                        <small class="md-sub">
                            Chỉ liệt kê ống <b>{{ $assessGroupName }} ({{ $assessGroupCode }})</b> còn hiệu lực và
                            <b>chưa có phiếu đánh giá</b>. Ống đã có phiếu chưa huỷ không xuất hiện lại ở đây;
                            muốn lập phiếu mới thì huỷ phiếu cũ trước.
                        </small>
                    </div>

                    @if ($imports->isEmpty())
                        <div class="md-hint mb-3">
                            <i class="fas fa-triangle-exclamation mr-1"></i>
                            Phòng <b>{{ session('user')['selected_department'] }}</b> chưa có ống
                            <b>{{ $assessGroupName }} ({{ $assessGroupCode }})</b> nào cần lập phiếu - hoặc tất cả
                            đều đã có phiếu đánh giá rồi.
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Chất Chuẩn</label>
                            <input type="text" class="form-control ssa-readonly ssa-pv-name" readonly value="">
                        </div>

                        <div class="form-group col-md-2">
                            <label>Số Lô</label>
                            <input type="text" class="form-control ssa-readonly ssa-pv-batch" readonly value="">
                        </div>

                        <div class="form-group col-md-2">
                            <label>Ngày Nhập Kho</label>
                            <input type="text" class="form-control ssa-readonly ssa-pv-imported" readonly value="">
                        </div>

                        <div class="form-group col-md-2">
                            <label>Hạn Dùng NSX</label>
                            <input type="text" class="form-control ssa-readonly ssa-pv-expired" readonly value="">
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ghi Chú</label>
                            <input type="text" name="note" maxlength="255"
                                class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                                value="{{ old('note') }}" placeholder="Ví dụ: Theo dõi sau khi mở ống">
                            @if ($bag->has('note'))
                                <span class="md-error">{{ $bag->first('note') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Ngày Bắt Đầu Đánh Giá <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                class="form-control ssa-start-input {{ $bag->has('start_date') ? 'is-invalid' : '' }}"
                                value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                            @if ($bag->has('start_date'))
                                <span class="md-error">{{ $bag->first('start_date') }}</span>
                            @endif
                            <small class="md-sub">Mốc gốc để tính ngày kiểm dự kiến.</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Chu Kỳ Đánh Giá (tháng) <span class="text-danger">*</span></label>
                            <input type="number" name="assessment_period" min="1" max="60" step="1"
                                class="form-control ssa-period-input {{ $bag->has('assessment_period') ? 'is-invalid' : '' }}"
                                value="{{ old('assessment_period', 3) }}" required>
                            @if ($bag->has('assessment_period'))
                                <span class="md-error">{{ $bag->first('assessment_period') }}</span>
                            @endif
                            <small class="md-sub">Khoảng cách giữa hai thời điểm kiểm liên tiếp.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Điền Nhanh Các Thời Điểm Kiểm</label>
                            <div class="input-group">
                                <input type="number" class="form-control ssa-fill-count" min="1" max="20"
                                    step="1" value="4" placeholder="Số mốc">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input ssa-fill-zero"
                                                id="ssaFillZero">
                                            <label class="custom-control-label" for="ssaFillZero">Kèm mốc ban
                                                đầu</label>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary ssa-fill-run">
                                        <i class="fas fa-bolt mr-1"></i> Điền
                                    </button>
                                </div>
                            </div>
                            <small class="md-sub">Điền đè lên các dòng đang có: 4 mốc × chu kỳ 3 tháng thành 3, 6,
                                9, 12 tháng.</small>
                        </div>
                    </div>

                    {{-- ============ CÁC MỐC ĐÁNH GIÁ ============ --}}
                    <div class="ssa-items-head">
                        <span><i class="fas fa-list-ol mr-1"></i> Các Mốc Đánh Giá</span>
                        <button type="button" class="btn btn-sm btn-outline-primary ssa-row-add">
                            <i class="fas fa-plus mr-1"></i> Thêm dòng
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm ssa-items-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 170px">Thời Điểm Kiểm (tháng)</th>
                                    <th class="text-center" style="width: 160px">Ngày Kiểm Dự Kiến</th>
                                    <th style="min-width: 380px">Chỉ Tiêu Kiểm</th>
                                    <th style="min-width: 240px">Ghi Chú</th>
                                    <th class="text-center" style="width: 55px"></th>
                                </tr>
                            </thead>
                            <tbody class="ssa-items-body">
                                @foreach ($ssaOldItems as $index => $row)
                                    @php
                                        $rowTestings = (array) ($row['testings'] ?? []);
                                        $rowTpError = $bag->first('items.' . $index . '.timepoint');
                                        $rowCritError = $bag->first('items.' . $index . '.testings');
                                        $rowNoteError = $bag->first('items.' . $index . '.note');
                                    @endphp
                                    <tr class="ssa-item-row">
                                        <td>
                                            <input type="number" name="items[{{ $index }}][timepoint]" min="0"
                                                max="127" step="1"
                                                class="form-control form-control-sm ssa-tp {{ $rowTpError ? 'is-invalid' : '' }}"
                                                value="{{ $row['timepoint'] ?? '' }}" placeholder="VD: 6">
                                            @if ($rowTpError)
                                                <span class="md-error">{{ $rowTpError }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle ssa-due-cell">—</td>
                                        <td>
                                            <select name="items[{{ $index }}][testings][]"
                                                class="form-control form-control-sm ssa-crit {{ $rowCritError ? 'is-invalid' : '' }}"
                                                multiple>
                                                @foreach ($criterias as $criteria)
                                                    <option value="{{ $criteria }}"
                                                        {{ in_array($criteria, $rowTestings) ? 'selected' : '' }}>
                                                        {{ $criteria }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if ($rowCritError)
                                                <span class="md-error">{{ $rowCritError }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $index }}][note]" maxlength="255"
                                                class="form-control form-control-sm {{ $rowNoteError ? 'is-invalid' : '' }}"
                                                value="{{ $row['note'] ?? '' }}">
                                            @if ($rowNoteError)
                                                <span class="md-error">{{ $rowNoteError }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-danger ssa-row-remove"
                                                title="Xoá dòng">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (empty($criterias))
                        <div class="md-hint">
                            <i class="fas fa-triangle-exclamation mr-1"></i>
                            Chưa có chỉ tiêu kiểm nào trong <b>Dữ Liệu Gốc → Chỉ Tiêu Kiểm</b>. Hãy khai chỉ tiêu ở
                            đó trước, hoặc lưu phiếu rồi bổ sung chỉ tiêu cho từng mốc sau.
                        </div>
                    @else
                        <div class="md-hint">
                            <i class="fas fa-info-circle mr-1"></i>
                            <b>Ngày kiểm dự kiến</b> = ngày bắt đầu + thời điểm kiểm, hệ thống tự tính khi lưu.
                            Chỉ tiêu kiểm lấy từ <b>Dữ Liệu Gốc → Chỉ Tiêu Kiểm</b>, mỗi mốc chọn tối đa
                            {{ $maxTestings }} chỉ tiêu. Dòng để trống thời điểm kiểm sẽ được bỏ qua.
                        </div>
                    @endif
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
        var $modal = $('#createModal');
        var $body = $modal.find('.ssa-items-body');
        var critOptions = @json($ssaCriteriaOptions);

        /* ---------- Ống chuẩn đang chọn ---------- */
        function previewImport() {
            var $select = $modal.find('.ssa-import-select');
            var info = ($select.data('info') || {})[$select.val()] || {};

            $modal.find('.ssa-pv-name').val(info.standard_name || '');
            $modal.find('.ssa-pv-batch').val(info.batch_no || '');
            $modal.find('.ssa-pv-imported').val(info.imported_date || '');
            $modal.find('.ssa-pv-expired').val(info.expired_date || '');
        }

        /* ---------- Ngày kiểm dự kiến ---------- */
        /*
        | Cộng tháng theo kiểu "không tràn tháng", đúng như addMonthsNoOverflow của Carbon
        | bên Controller: 31/01 + 1 tháng = 28/02 chứ không nhảy sang 03/03.
        */
        function addMonths(startDate, months) {
            var parts = String(startDate).split('-');

            if (parts.length !== 3) return null;

            var day = parseInt(parts[2], 10);
            var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1 + months, 1);
            var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

            date.setDate(Math.min(day, lastDay));

            return date;
        }

        function previewDues() {
            var start = $modal.find('.ssa-start-input').val();

            $body.find('.ssa-item-row').each(function() {
                var timepoint = parseInt($(this).find('.ssa-tp').val(), 10);
                var $cell = $(this).find('.ssa-due-cell');

                if (!start || isNaN(timepoint) || timepoint < 0) {
                    $cell.text('—');

                    return;
                }

                var date = addMonths(start, timepoint);

                if (!date) {
                    $cell.text('—');

                    return;
                }

                var dd = String(date.getDate()).padStart(2, '0');
                var mm = String(date.getMonth() + 1).padStart(2, '0');

                $cell.text(dd + '/' + mm + '/' + date.getFullYear());
            });
        }

        /* ---------- Ô chọn nhiều chỉ tiêu kiểm ---------- */
        function initCriteria($scope) {
            $scope.find('.ssa-crit').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) return;

                $(this).select2({
                    theme: 'bootstrap4',
                    dropdownParent: $modal,
                    width: '100%',
                    placeholder: '-- Chọn chỉ tiêu --',
                    closeOnSelect: false,
                    language: {
                        noResults: function() {
                            return 'Không tìm thấy chỉ tiêu phù hợp';
                        }
                    }
                });
            });
        }

        /* ---------- Thêm / xoá dòng ---------- */
        // Chỉ số dòng chạy tăng dần, không đánh lại sau khi xoá: PHP đọc mảng items
        // bằng array_values nên khoảng trống ở giữa không ảnh hưởng gì.
        var rowIndex = {{ count($ssaOldItems) }};

        function addRow(timepoint) {
            var html = '<tr class="ssa-item-row">' +
                '<td><input type="number" name="items[' + rowIndex +
                '][timepoint]" min="0" max="127" step="1" class="form-control form-control-sm ssa-tp" value="' +
                (timepoint === undefined ? '' : timepoint) + '" placeholder="VD: 6"></td>' +
                '<td class="text-center align-middle ssa-due-cell">—</td>' +
                '<td><select name="items[' + rowIndex +
                '][testings][]" class="form-control form-control-sm ssa-crit" multiple>' + critOptions +
                '</select></td>' +
                '<td><input type="text" name="items[' + rowIndex +
                '][note]" maxlength="255" class="form-control form-control-sm"></td>' +
                '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger ssa-row-remove" title="Xoá dòng"><i class="fas fa-trash"></i></button></td>' +
                '</tr>';

            rowIndex++;

            var $row = $(html).appendTo($body);

            initCriteria($row);

            return $row;
        }

        $modal.on('click', '.ssa-row-add', function() {
            addRow();
            previewDues();
        });

        $modal.on('click', '.ssa-row-remove', function() {
            var $row = $(this).closest('.ssa-item-row');

            // Xoá hết thì giữ lại một dòng trống cho dễ nhập tiếp
            if ($body.find('.ssa-item-row').length <= 1) {
                $row.find('.ssa-tp, [name$="[note]"]').val('');
                $row.find('.ssa-crit').val(null).trigger('change');
                $row.find('.ssa-due-cell').text('—');

                return;
            }

            $row.find('.ssa-crit').select2('destroy');
            $row.remove();
        });

        /* ---------- Điền nhanh theo chu kỳ ---------- */
        $modal.on('click', '.ssa-fill-run', function() {
            var period = parseInt($modal.find('.ssa-period-input').val(), 10);
            var count = parseInt($modal.find('.ssa-fill-count').val(), 10);
            var withZero = $modal.find('.ssa-fill-zero').is(':checked');

            if (isNaN(period) || period < 1) {
                Swal.fire({
                    title: 'Chưa có chu kỳ',
                    text: 'Hãy nhập chu kỳ đánh giá (số tháng) trước khi điền nhanh.',
                    icon: 'warning',
                    confirmButtonColor: '#2E7BC4',
                    confirmButtonText: 'Đã hiểu'
                });

                return;
            }

            if (isNaN(count) || count < 1) count = 1;

            $body.find('.ssa-crit').select2('destroy');
            $body.empty();

            if (withZero) addRow(0);

            for (var i = 1; i <= count; i++) {
                var timepoint = period * i;

                if (timepoint > 127) break;

                addRow(timepoint);
            }

            previewDues();
        });

        /* ---------- Sự kiện ---------- */
        $modal.on('change', '.ssa-import-select', previewImport);
        $modal.on('change input', '.ssa-start-input, .ssa-tp', previewDues);

        // Mở lại modal thì dựng lại ô chọn của các dòng vừa thêm bằng JS. Nút "Thêm mới"
        // dùng chung có gọi form.reset() trước khi mở, select2 phải được báo để vẽ lại.
        $modal.on('shown.bs.modal', function() {
            initCriteria($body);
            $body.find('.ssa-crit').trigger('change');
            previewImport();
            previewDues();
        });

        initCriteria($body);
        previewImport();
        previewDues();
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
