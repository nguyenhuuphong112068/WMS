@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 1050px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Điều Chỉnh Phiếu Nhập Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <input type="hidden" name="group_key" value="{{ old('group_key') }}">
                <input type="hidden" name="imported_date" value="{{ old('imported_date') }}">

                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Mã Ống Chuẩn</label>
                            <input type="text" name="code" class="form-control imp-readonly" readonly tabindex="-1"
                                value="{{ old('code') }}">
                            <small class="md-sub">Mã đã cấp và đã in ra nhãn, không sinh lại khi điều chỉnh.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Nhóm Chuẩn</label>
                            <input type="text" class="form-control imp-readonly sd-group-view" readonly tabindex="-1"
                                value="">
                            <small class="md-sub">Nhóm nằm trong mã ống chuẩn nên không đổi được.</small>
                            @if ($bag->has('group_key'))
                                <span class="md-error">{{ $bag->first('group_key') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Chất Chuẩn <span class="text-danger">*</span></label>
                            <select name="category_id"
                                class="form-control imp-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                                required>
                                <option value="">-- Chọn chất chuẩn --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->code }} - {{ $category->standard_name }}
                                        (v{{ $category->version }}{{ $category->unit_short_name ? ', ' . $category->unit_short_name : '' }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('category_id'))
                                <span class="md-error">{{ $bag->first('category_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Số Lượng <span class="text-danger">*</span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Hàm Lượng</label>
                            <input type="text" name="potency" maxlength="100"
                                class="form-control {{ $bag->has('potency') ? 'is-invalid' : '' }}"
                                value="{{ old('potency') }}" placeholder="Ví dụ: 99.5%, 1000 µg/mL">
                            @if ($bag->has('potency'))
                                <span class="md-error">{{ $bag->first('potency') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Độ Ẩm (%)</label>
                            <input type="text" name="moisture" maxlength="100"
                                class="form-control {{ $bag->has('moisture') ? 'is-invalid' : '' }}"
                                value="{{ old('moisture') }}" placeholder="Ví dụ: 0.3%">
                            @if ($bag->has('moisture'))
                                <span class="md-error">{{ $bag->first('moisture') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số Phiếu Kiểm Nghiệm Gốc (CoA)</label>
                            <input type="text" name="coa_no" maxlength="100"
                                class="form-control {{ $bag->has('coa_no') ? 'is-invalid' : '' }}"
                                value="{{ old('coa_no') }}">
                            @if ($bag->has('coa_no'))
                                <span class="md-error">{{ $bag->first('coa_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Vị Trí Lưu Trữ</label>
                            <select name="location_id"
                                class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa xếp vị trí --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->warehouse_name ?: '—' }} /
                                        {{ $location->room_name ?: '—' }} /
                                        {{ $location->shelf_name ?: '—' }} /
                                        {{ $location->name }} ({{ $location->code }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('location_id'))
                                <span class="md-error">{{ $bag->first('location_id') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mục Đích Sử Dụng</label>
                            <select name="purpose_id"
                                class="form-control imp-select {{ $bag->has('purpose_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn mục đích --</option>
                                @foreach ($purposes as $purpose)
                                    <option value="{{ $purpose->id }}"
                                        {{ old('purpose_id') == $purpose->id ? 'selected' : '' }}>
                                        {{ $purpose->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('purpose_id'))
                                <span class="md-error">{{ $bag->first('purpose_id') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Cụm Hạn Dùng --}}
                    <div class="card p-3 mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <label class="font-weight-bold text-primary mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i> Thiết Lập Hạn Dùng & Retest
                        </label>

                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 18px;">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="upExpTypeDefined" name="expiry_type" value="defined"
                                    class="custom-control-input sd-up-exp-type">
                                <label class="custom-control-label" for="upExpTypeDefined">Hạn dùng xác định</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="upExpTypeUndetermined" name="expiry_type" value="undetermined"
                                    class="custom-control-input sd-up-exp-type">
                                <label class="custom-control-label" for="upExpTypeUndetermined">Chưa xác định (Check online)</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="upExpTypeRetest" name="expiry_type" value="retest"
                                    class="custom-control-input sd-up-exp-type">
                                <label class="custom-control-label" for="upExpTypeRetest">Cần Retest định kỳ</label>
                            </div>
                        </div>

                        <div class="form-row mt-2 sd-up-exp-defined-box">
                            <div class="form-group col-md-6 mb-0">
                                <label>Hạn Sử Dụng</label>
                                <input type="date" name="expired_date"
                                    class="form-control sd-up-expired-date {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                    value="{{ old('expired_date') }}">
                            </div>

                            <div class="form-group col-md-6 mb-0 sd-up-retest-box" style="display:none;">
                                <label>Chu Kỳ Retest (Tháng)</label>
                                <input type="number" name="retest_interval_months" min="1" max="1200"
                                    class="form-control sd-up-retest-months {{ $bag->has('retest_interval_months') ? 'is-invalid' : '' }}"
                                    value="{{ old('retest_interval_months') }}" placeholder="Ví dụ: 6, 12">
                            </div>
                        </div>

                        <div class="sd-up-exp-undetermined-msg text-muted mt-2" style="display:none;">
                            <i class="fas fa-info-circle text-warning mr-1"></i>
                            <em>Chất chuẩn chưa có hạn sử dụng cố định từ NSX. Trạng thái: <b>Check online</b>.</em>
                        </div>
                    </div>

                    {{-- Checkboxes & Dạng Chuẩn --}}
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light" style="min-height: 80px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input sd-up-weight-ctrl" id="up_weight_controlled" name="weight_controlled" value="1">
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="up_weight_controlled">
                                        <i class="fas fa-balance-scale mr-1 text-secondary"></i> Chuẩn có cần kiểm soát khối lượng
                                    </label>
                                </div>

                                {{-- Dạng chuẩn chỉ hiện khi tick chọn kiểm soát khối lượng --}}
                                <div class="sd-up-standard-form-box mt-2 pt-2 border-top" style="display:none;">
                                    <label class="small font-weight-bold mb-1 text-primary d-block">Dạng Chuẩn <span class="text-danger">*</span></label>
                                    <select name="standard_form" class="form-control sd-up-standard-form-select" style="height: 38px !important; font-size: 0.9rem;">
                                        <option value="">-- Chọn dạng chuẩn --</option>
                                        <option value="Dạng Bột Rời">Dạng Bột Rời</option>
                                        <option value="Dạng Bột Mịn">Dạng Bột Mịn</option>
                                        <option value="Dạng Sệt">Dạng Sệt</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light" style="min-height: 80px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input sd-up-req-aliquot" id="up_requires_aliquot" name="requires_aliquot" value="1">
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="up_requires_aliquot">
                                        <i class="fas fa-vial mr-1 text-info"></i> Chuẩn cần triết ống trước khi sử dụng
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Đính kèm file --}}
                    <div class="form-group">
                        <label>Đính Kèm Thêm File Hồ Sơ</label>
                        <input type="file" name="attachments[]" multiple class="form-control-file border p-1 rounded">
                        <div class="sd-existing-attachments mt-2"></div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <input type="text" name="note" maxlength="500"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            value="{{ old('note') }}">
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Nhập sai thông tin, điều chỉnh lại cho đúng thực tế"
                            required>{{ old('reason') }}</textarea>
                        @if ($bag->has('reason'))
                            <span class="md-error">{{ $bag->first('reason') }}</span>
                        @endif
                        <small class="md-sub">Bắt buộc. Lý do được lưu vào lịch sử điều chỉnh của phiếu.</small>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mỗi lần điều chỉnh ghi thêm <b>một dòng lịch sử</b>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu điều chỉnh
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $upModal = $('#updateModal');

        // Toggle dạng chuẩn theo checkbox kiểm soát khối lượng
        $(document).on('change', '#updateModal .sd-up-weight-ctrl', function() {
            var isChecked = $(this).is(':checked');
            if (isChecked) {
                $upModal.find('.sd-up-standard-form-box').slideDown(150);
            } else {
                $upModal.find('.sd-up-standard-form-box').slideUp(150);
                $upModal.find('.sd-up-standard-form-select').val('');
            }
        });

        // Toggle loại hạn dùng trong update
        $(document).on('change', '#updateModal .sd-up-exp-type', function() {
            var val = $(this).val();
            if (val === 'undetermined') {
                $upModal.find('.sd-up-exp-defined-box').slideUp(150);
                $upModal.find('.sd-up-exp-undetermined-msg').slideDown(150);
                $upModal.find('.sd-up-retest-box').hide();
            } else if (val === 'retest') {
                $upModal.find('.sd-up-exp-defined-box').slideDown(150);
                $upModal.find('.sd-up-exp-undetermined-msg').slideUp(150);
                $upModal.find('.sd-up-retest-box').slideDown(150);
            } else {
                $upModal.find('.sd-up-exp-defined-box').slideDown(150);
                $upModal.find('.sd-up-exp-undetermined-msg').slideUp(150);
                $upModal.find('.sd-up-retest-box').slideUp(150);
            }
        });

        /* Đổ dữ liệu vào modal update khi bấm sửa */
        $(document).on('click', '#mdTable .btn-md-edit', function() {
            var row = $(this).data('row') || {};

            $upModal.find('.sd-group-view').val(row.group_label || '—');
            $upModal.find('input[name="id"]').val(row.id || '');
            $upModal.find('input[name="code"]').val(row.code || '');
            $upModal.find('input[name="group_key"]').val(row.group_key || '');
            $upModal.find('select[name="category_id"]').val(row.category_id || '').trigger('change');
            $upModal.find('input[name="amount"]').val(row.amount || '');
            $upModal.find('input[name="batch_no"]').val(row.batch_no || '');
            $upModal.find('input[name="coa_no"]').val(row.coa_no || '');
            $upModal.find('input[name="potency"]').val(row.potency || '');
            $upModal.find('input[name="moisture"]').val(row.moisture || '');
            $upModal.find('select[name="location_id"]').val(row.location_id || '').trigger('change');
            $upModal.find('select[name="purpose_id"]').val(row.purpose_id || '').trigger('change');
            $upModal.find('input[name="imported_date"]').val(row.imported_date || '');
            $upModal.find('input[name="note"]').val(row.note || '');

            // Checkbox kiểm soát khối lượng & Dạng chuẩn
            var isWeightCtrl = !!row.weight_controlled;
            $upModal.find('.sd-up-weight-ctrl').prop('checked', isWeightCtrl);
            if (isWeightCtrl) {
                $upModal.find('.sd-up-standard-form-box').show();
                $upModal.find('.sd-up-standard-form-select').val(row.standard_form || '');
            } else {
                $upModal.find('.sd-up-standard-form-box').hide();
                $upModal.find('.sd-up-standard-form-select').val('');
            }

            // Checkbox triết ống
            $upModal.find('.sd-up-req-aliquot').prop('checked', !!row.requires_aliquot);

            // Expiry type
            var expType = row.expiry_type || 'defined';
            $upModal.find('.sd-up-exp-type[value="' + expType + '"]').prop('checked', true).trigger('change');
            $upModal.find('input[name="expired_date"]').val(row.expired_date || '');
            $upModal.find('input[name="retest_interval_months"]').val(row.retest_interval_months || '');

            // Files đính kèm hiện có
            var files = row.attachments || [];
            var $filesContainer = $upModal.find('.sd-existing-attachments').empty();
            if (files.length) {
                var html = '<div class="small font-weight-bold text-muted mb-1">File đã đính kèm:</div><ul class="list-unstyled mb-0">';
                files.forEach(function(f) {
                    html += '<li class="d-flex align-items-center justify-content-between py-1 border-bottom">' +
                        '<a href="/import/standardImport/download-attachment/' + f.id + '" target="_blank">' +
                        '<i class="fas fa-paperclip mr-1"></i> ' + f.file_name + '</a>' +
                        '<button type="button" class="btn btn-xs btn-outline-danger sd-btn-del-file" data-id="' + f.id + '">' +
                        '<i class="fas fa-trash"></i></button>' +
                        '</li>';
                });
                html += '</ul>';
                $filesContainer.html(html);
            }
        });

        // Xoá file đính kèm
        $(document).on('click', '.sd-btn-del-file', function(e) {
            e.preventDefault();
            var fileId = $(this).data('id');
            var $li = $(this).closest('li');

            if (!confirm('Bạn có chắc chắn muốn xoá file này?')) return;

            $.post('{{ route($impRoute . "deleteAttachment") }}', {
                _token: '{{ csrf_token() }}',
                id: fileId
            }, function(res) {
                if (res.success) {
                    $li.fadeOut(200, function() { $(this).remove(); });
                }
            }).fail(function() {
                alert('Không thể xoá file lúc này.');
            });
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
