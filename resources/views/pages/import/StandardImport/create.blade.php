@php
    $bag = $errors->getBag('createErrors');
    $oldExpiryType = old('expiry_type', 'defined');
    $oldWeightCtrl = old('weight_controlled', 0);
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" role="document" style="max-width: 1050px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $impIcon }}"></i> Nhập Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($impRoute . 'store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Ngày nhập mặc định theo ngày hiện hành --}}
                <input type="hidden" name="imported_date" class="sd-imported-date"
                    value="{{ old('imported_date', now()->format('Y-m-d')) }}">

                <div class="modal-body">

                    {{-- Nhóm 1: Thông tin cơ bản --}}
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Chất Chuẩn <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-outline-info mr-2" style="flex-shrink: 0;" data-toggle="modal" data-target="#selectStandardModal" title="Mở danh mục chất chuẩn để chọn">
                                    <i class="fas fa-list"></i>
                                </button>
                                <div style="flex: 1 1 auto; min-width: 0;">
                                    <select name="category_id"
                                        class="form-control imp-select sd-category {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                                        data-defaults="{{ json_encode($categoryDefaults) }}" required>
                                        <option value="">-- Chọn chất chuẩn --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->code }} - {{ $category->standard_name }}
                                                (v{{ $category->version }}{{ $category->unit_short_name ? ', ' . $category->unit_short_name : '' }}{{ $category->manufacturer_short_name ? ', ' . $category->manufacturer_short_name : '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if ($bag->has('category_id'))
                                <span class="md-error d-block mt-1">{{ $bag->first('category_id') }}</span>
                            @endif
                            <small class="md-sub">Chỉ hiện chất chuẩn trong Danh Mục đã duyệt và hoạt động.</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Mã Ống Chuẩn (Dự kiến)</label>
                            <input type="hidden" name="group_key" class="sd-group-input"
                                value="{{ old('group_key') }}">
                            <input type="text" class="form-control imp-readonly sd-code-preview" readonly
                                data-codes="{{ json_encode($codePreviews) }}" data-placeholder="Chọn chất chuẩn trước"
                                value="{{ old('group_key') && isset($codePreviews[old('group_key')]) ? $codePreviews[old('group_key')] : 'Chọn chất chuẩn trước' }}">
                            <small class="md-sub">
                                Sinh tự động theo nhóm của chất chuẩn.
                            </small>
                        </div>

                        <div class="col-md-12 mb-3 sd-info-box-wrap" style="display: none;">
                            <div class="alert alert-info py-2 px-3 mb-0 sd-info-box" style="font-size: 0.95rem; line-height: 1.5;"></div>
                        </div>
                    </div>

                    {{-- Nhóm 2: Số lượng, Số ống nhập, Vị trí lưu trữ, Chỉ tiêu kiểm --}}
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Lượng / Ống <span class="text-danger">*</span> <span id="sd-unit-label" class="text-primary font-weight-bold"></span></label>
                            <input type="number" name="amount" step="0.0001" min="0.0001"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}"
                                value="{{ old('amount') }}" placeholder="Ví dụ: 50" required>
                            @if ($bag->has('amount'))
                                <span class="md-error">{{ $bag->first('amount') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Số Ống Nhập <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" min="1" max="50"
                                class="form-control {{ $bag->has('quantity') ? 'is-invalid' : '' }}"
                                value="{{ old('quantity', 1) }}" required>
                            @if ($bag->has('quantity'))
                                <span class="md-error">{{ $bag->first('quantity') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Vị Trí Lưu Trữ</label>
                            <select name="location_id"
                                class="form-control imp-select sd-location-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn --</option>
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

                        <div class="form-group col-md-3">
                            <label>Chỉ Tiêu Kiểm</label>
                            @php
                                $oldPurposes = (array) old('purpose_id', []);
                            @endphp
                            <select name="purpose_id[]"
                                class="form-control imp-select sd-purpose-select {{ $bag->has('purpose_id') ? 'is-invalid' : '' }}"
                                multiple="multiple" data-placeholder="-- Chọn --">
                                @foreach ($purposes as $purpose)
                                    <option value="{{ $purpose->id }}"
                                        {{ in_array($purpose->id, $oldPurposes) ? 'selected' : '' }}>
                                        {{ $purpose->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('purpose_id'))
                                <span class="md-error">{{ $bag->first('purpose_id') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Nhóm 3: Lô, Hàm lượng, Ẩm, CoA --}}
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Số Lô</label>
                            <input type="text" name="batch_no" maxlength="100"
                                class="form-control {{ $bag->has('batch_no') ? 'is-invalid' : '' }}"
                                value="{{ old('batch_no') }}" placeholder="Ví dụ: LOT-2026">
                            @if ($bag->has('batch_no'))
                                <span class="md-error">{{ $bag->first('batch_no') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Hàm Lượng</label>
                            <input type="text" name="potency" maxlength="100"
                                class="form-control {{ $bag->has('potency') ? 'is-invalid' : '' }}"
                                value="{{ old('potency') }}" placeholder="Ví dụ: 99.5%">
                            @if ($bag->has('potency'))
                                <span class="md-error">{{ $bag->first('potency') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Độ Ẩm (%)</label>
                            <input type="text" name="moisture" maxlength="100"
                                class="form-control {{ $bag->has('moisture') ? 'is-invalid' : '' }}"
                                value="{{ old('moisture') }}" placeholder="Ví dụ: 0.3%">
                            @if ($bag->has('moisture'))
                                <span class="md-error">{{ $bag->first('moisture') }}</span>
                            @endif
                        </div>

                        <div class="form-group col-md-3">
                            <label>Số CoA</label>
                            <input type="text" name="coa_no" maxlength="100"
                                class="form-control {{ $bag->has('coa_no') ? 'is-invalid' : '' }}"
                                value="{{ old('coa_no') }}" placeholder="Ví dụ: CoA-145">
                            @if ($bag->has('coa_no'))
                                <span class="md-error">{{ $bag->first('coa_no') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Nhóm 5: Cụm Hạn Dùng Nâng Cao --}}
                    <div class="card p-3 mb-3"
                        style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <label class="font-weight-bold text-primary mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i> Thiết Lập Hạn Dùng & Retest
                        </label>

                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap: 18px;">
                            {{-- 1. Hạn dùng xác định --}}
                            <div class="custom-control custom-radio">
                                <input type="radio" id="expTypeSpecify" name="expiry_type" value="Specify"
                                    class="custom-control-input sd-exp-type"
                                    {{ in_array($oldExpiryType, ['Specify', 'defined']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expTypeSpecify">Hạn dùng xác định</label>
                            </div>

                            {{-- 2. Check online --}}
                            <div class="custom-control custom-radio">
                                <input type="radio" id="expTypeCheckOnline" name="expiry_type"
                                    value="check online" class="custom-control-input sd-exp-type"
                                    {{ in_array($oldExpiryType, ['check online', 'undetermined', 'unlimited']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expTypeCheckOnline">Chưa xác định (Check
                                    online)</label>
                            </div>

                            {{-- 3. Retest --}}
                            <div class="custom-control custom-radio">
                                <input type="radio" id="expTypeRetest" name="expiry_type" value="retest"
                                    class="custom-control-input sd-exp-type"
                                    {{ $oldExpiryType == 'retest' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expTypeRetest">Cần Retest định kỳ</label>
                            </div>

                            {{-- 4. Cần xác định lại hạn dùng nội bộ --}}
                            <div class="custom-control custom-radio" id="wrapExpTypeReEvaluation">
                                <input type="radio" id="expTypeReEvaluation" name="expiry_type"
                                    value="Requires_re-evaluation" class="custom-control-input sd-exp-type"
                                    {{ in_array($oldExpiryType, ['Requires_re-evaluation', 'requires_re-evaluation']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expTypeReEvaluation"
                                    id="labelExpTypeReEvaluation">
                                    Cần xác định lại hạn dùng nội bộ
                                </label>
                            </div>
                        </div>

                        <div class="form-row mt-2 sd-exp-defined-box"
                            style="{{ in_array($oldExpiryType, ['check online', 'undetermined', 'unlimited']) ? 'display:none;' : '' }}">
                            <div class="form-group col-md-12 mb-0">
                                <label class="sd-label-exp-date font-weight-bold">
                                    @if ($oldExpiryType == 'retest')
                                        Hạn Retest NSX <span class="text-danger">*</span>
                                    @else
                                        Hạn Sử Dụng (NSX) <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <div class="input-group">
                                    <input type="date" name="expired_date"
                                        class="form-control sd-expired-date {{ $bag->has('expired_date') ? 'is-invalid' : '' }}"
                                        value="{{ old('expired_date') }}"
                                        {{ !in_array($oldExpiryType, ['check online', 'undetermined', 'unlimited']) ? 'required' : '' }}>
                                    <div class="input-group-append sd-auto-calc-btn-wrap" style="display: none;">
                                        <button type="button" class="btn btn-outline-info sd-btn-calc-exp"
                                            title="Tính hạn dùng theo danh mục phòng">
                                            <i class="fas fa-magic"></i> Gợi ý
                                        </button>
                                    </div>
                                </div>
                                <small class="md-sub sd-exp-hint">
                                    @if ($oldExpiryType == 'retest')
                                        Hạn kiểm nghiệm lại (retest) do NSX công bố.
                                    @elseif ($oldExpiryType == 'Requires_re-evaluation')
                                        Hạn sử dụng NSX (kết hợp tính hạn dùng nội bộ sau khi mở ống).
                                    @else
                                        Hạn sử dụng ghi trên nhãn NSX.
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="sd-exp-undetermined-msg text-muted mt-2"
                            style="{{ in_array($oldExpiryType, ['check online', 'undetermined', 'unlimited']) ? '' : 'display:none;' }}">
                            <i class="fas fa-info-circle text-warning mr-1"></i>
                            <em>Chất chuẩn chưa có hạn sử dụng cố định từ NSX. Sẽ hiển thị trạng thái <b>Check
                                    online</b> và kiểm tra hạn trực tuyến trước khi mở ống.</em>
                        </div>
                    </div>

                    {{-- Nhóm 6: Phân loại đặc thù (Checkboxes & Dạng Chuẩn) --}}
                    <div class="form-row mb-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light" style="min-height: 80px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input sd-weight-ctrl"
                                        id="weight_controlled" name="weight_controlled" value="1"
                                        {{ $oldWeightCtrl ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer"
                                        for="weight_controlled">
                                        <i class="fas fa-balance-scale mr-1 text-secondary"></i> Chuẩn có cần kiểm soát
                                        khối lượng
                                    </label>
                                </div>

                                {{-- Dạng chuẩn chỉ hiện khi tick chọn kiểm soát khối lượng --}}
                                <div class="sd-standard-form-box mt-2 pt-2 border-top"
                                    style="{{ $oldWeightCtrl ? '' : 'display:none;' }}">
                                    <label class="small font-weight-bold mb-1 text-primary d-block">Dạng Chuẩn <span
                                            class="text-danger">*</span></label>
                                    <select name="standard_form" class="form-control sd-standard-form-select"
                                        style="height: 38px !important; font-size: 0.9rem;">
                                        <option value="">-- Chọn dạng chuẩn --</option>
                                        <option value="Dạng Bột Rời"
                                            {{ old('standard_form') == 'Dạng Bột Rời' ? 'selected' : '' }}>Dạng Bột Rời
                                        </option>
                                        <option value="Dạng Bột Mịn"
                                            {{ old('standard_form') == 'Dạng Bột Mịn' ? 'selected' : '' }}>Dạng Bột Mịn
                                        </option>
                                        <option value="Dạng Sệt"
                                            {{ old('standard_form') == 'Dạng Sệt' ? 'selected' : '' }}>Dạng Sệt
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light" style="min-height: 80px;">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="requires_aliquot"
                                        name="requires_aliquot" value="1"
                                        {{ old('requires_aliquot') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer"
                                        for="requires_aliquot">
                                        <i class="fas fa-vial mr-1 text-info"></i> Chuẩn cần triết ống trước khi sử
                                        dụng
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Nhóm 7: Đính kèm file hồ sơ & Ghi chú --}}
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Đính Kèm File Hồ Sơ (CoA...)</label>
                            <input type="file" name="attachments[]" multiple
                                class="form-control-file border p-1 rounded">
                            <small class="md-sub">Đính kèm nhiều file (tối đa 10MB/file).</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ghi Chú</label>
                            <textarea name="note" rows="2" maxlength="500"
                                class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                                placeholder="Ghi chú thêm...">{{ old('note') }}</textarea>
                            @if ($bag->has('note'))
                                <span class="md-error">{{ $bag->first('note') }}</span>
                            @endif
                        </div>
                    </div>


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Lưu Phiếu Nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $modal = $('#createModal');

        // Toggle dạng chuẩn theo checkbox kiểm soát khối lượng
        $(document).on('change', '#createModal .sd-weight-ctrl', function() {
            var isChecked = $(this).is(':checked');
            if (isChecked) {
                $modal.find('.sd-standard-form-box').slideDown(150);
            } else {
                $modal.find('.sd-standard-form-box').slideUp(150);
                $modal.find('.sd-standard-form-select').val('');
            }
        });

        // Toggle loại hạn dùng
        $(document).on('change', '#createModal .sd-exp-type', function() {
            var val = $(this).val();
            var $dateInput = $modal.find('.sd-expired-date');
            var $label = $modal.find('.sd-label-exp-date');
            var $hint = $modal.find('.sd-exp-hint');

            if (val === 'check online' || val === 'undetermined' || val === 'unlimited') {
                $modal.find('.sd-exp-defined-box').slideUp(150);
                $modal.find('.sd-exp-undetermined-msg').slideDown(150);
                $dateInput.prop('required', false);
            } else if (val === 'retest') {
                $modal.find('.sd-exp-defined-box').slideDown(150);
                $modal.find('.sd-exp-undetermined-msg').slideUp(150);
                $label.html('Hạn Retest NSX <span class="text-danger">*</span>');
                $hint.text('Hạn kiểm nghiệm lại (retest) do NSX công bố.');
                $dateInput.prop('required', true);
            } else if (val === 'Requires_re-evaluation') {
                $modal.find('.sd-exp-defined-box').slideDown(150);
                $modal.find('.sd-exp-undetermined-msg').slideUp(150);
                $label.html('Hạn Sử Dụng (NSX) <span class="text-danger">*</span>');
                $hint.text('Hạn sử dụng NSX (kết hợp tính hạn dùng nội bộ sau khi mở ống).');
                $dateInput.prop('required', true);
            } else {
                // Specify
                $modal.find('.sd-exp-defined-box').slideDown(150);
                $modal.find('.sd-exp-undetermined-msg').slideUp(150);
                $label.html('Hạn Sử Dụng (NSX) <span class="text-danger">*</span>');
                $hint.text('Hạn sử dụng ghi trên nhãn NSX.');
                $dateInput.prop('required', true);
            }
        });

        // Tự động đồng bộ khi chọn chất chuẩn
        function syncCategoryDetails($form) {
            var $cat = $form.find('.sd-category');
            var catId = $cat.val();
            var defaultsMap = $cat.data('defaults') || {};
            var item = defaultsMap[catId] || null;

            if (item) {
                if (item.info_html) {
                    $form.find('.sd-info-box').html(item.info_html);
                    $form.find('.sd-info-box-wrap').slideDown('fast');
                } else {
                    $form.find('.sd-info-box-wrap').hide();
                }

                if (item.unit_short_name) {
                    $form.find('#sd-unit-label').text('(' + item.unit_short_name + ')');
                } else {
                    $form.find('#sd-unit-label').text('');
                }

                // Nhóm chuẩn & Code preview
                $form.find('.sd-group-input').val(item.group_key || '');
                var previewMap = $form.find('.sd-code-preview').data('codes') || {};
                $form.find('.sd-code-preview').val(previewMap[item.group_key] || item.group_code || '');

                // Vị trí lưu trữ gợi ý
                if (item.location_id) {
                    $form.find('.sd-location-select').val(item.location_id).trigger('change');
                }

                // Cần xác định lại hạn dùng nội bộ: Chỉ được chọn khi standard_categories.shelf_life_months > 0
                var shelfLife = parseInt(item.shelf_life_months, 10) || 0;
                var $optReEval = $form.find('#expTypeReEvaluation');
                var $lblReEval = $form.find('#labelExpTypeReEvaluation');

                if (shelfLife > 0) {
                    $form.find('.sd-exp-type').not($optReEval).prop('disabled', true);
                    $optReEval.prop('disabled', false).prop('checked', true).trigger('change');
                    $lblReEval.removeClass('text-muted').attr('title', 'Chuẩn có hạn dùng nội bộ: ' +
                        shelfLife + ' tháng');
                    $form.find('.sd-auto-calc-btn-wrap').show();
                    $form.find('.sd-btn-calc-exp').data('months', shelfLife);
                } else {
                    $form.find('.sd-exp-type').not($optReEval).prop('disabled', false);
                    $optReEval.prop('disabled', true);
                    $lblReEval.addClass('text-muted').attr('title',
                        'Chất chuẩn chưa có thiết lập hạn dùng nội bộ (shelf_life_months > 0)');
                    if ($optReEval.is(':checked')) {
                        $form.find('#expTypeSpecify').prop('checked', true).trigger('change');
                    }
                    $form.find('.sd-auto-calc-btn-wrap').hide();
                }
            } else {
                $form.find('.sd-info-box-wrap').hide();
                $form.find('.sd-group-input').val('');
                $form.find('.sd-code-preview').val($form.find('.sd-code-preview').data('placeholder') || '');
                $form.find('.sd-auto-calc-btn-wrap').hide();
                $form.find('#expTypeReEvaluation').prop('disabled', true);
                $form.find('#labelExpTypeReEvaluation').addClass('text-muted');
            }
        }

        // Bấm nút gợi ý hạn dùng (tính từ ngày hôm nay / ngày nhập)
        $(document).on('click', '#createModal .sd-btn-calc-exp', function() {
            var months = parseInt($(this).data('months'), 10);
            var impDateVal = $modal.find('.sd-imported-date').val() || new Date().toISOString().split(
                'T')[0];
            if (!months || !impDateVal) return;

            var d = new Date(impDateVal);
            if (isNaN(d.getTime())) return;

            d.setMonth(d.getMonth() + months);
            var yyyy = d.getFullYear();
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var dd = String(d.getDate()).padStart(2, '0');

            $modal.find('.sd-expired-date').val(yyyy + '-' + mm + '-' + dd);
        });

        $(document).on('change', '#createModal .sd-category', function() {
            syncCategoryDetails($(this).closest('form'));
        });

        // Trigger khi mở modal
        $(document).on('click', '.btn-md-create', function() {
            syncCategoryDetails($modal.find('form'));
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

{{-- Modal Chọn Chất Chuẩn --}}
<div class="modal fade" id="selectStandardModal" tabindex="-1" role="dialog" style="z-index: 1060;" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn Chất Chuẩn</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover w-100" id="tableSelectStandard">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">STT</th>
                                <th>MÃ CHUẨN</th>
                                <th>TÊN CHẤT CHUẨN</th>
                                <th>SỐ CAS</th>
                                <th>NGUỒN GỐC / NSX</th>
                                <th>TỶ TRỌNG</th>
                                <th>ĐIỀU KIỆN BẢO QUẢN</th>
                                <th>VERSION</th>
                                <th>PHÂN NHÓM CHUẨN</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $category->code }}</strong></td>
                                    <td>{{ $category->standard_name }}</td>
                                    <td>{{ $category->name_cas_no ?: '—' }}</td>
                                    <td>
                                        <div class="md-sub">{{ $category->manufacturer_name ?: '—' }}</div>
                                        @if ($category->manufacturer_short_name)
                                            <span class="badge badge-light border">{{ $category->manufacturer_short_name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->density !== null ? $category->density : '—' }}</td>
                                    <td>{{ $category->storage_condition_name ?: '—' }}</td>
                                    <td class="text-center"><span class="badge badge-pill badge-light border">v{{ $category->version }}</span></td>
                                    <td>
                                        @if($category->groups)
                                            @foreach(json_decode($category->groups) as $g)
                                                <span class="badge badge-primary">{{ $g }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-standard" data-id="{{ $category->id }}">Chọn</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $dt = $('#tableSelectStandard').DataTable({
            pageLength: 10,
            lengthChange: false,
            language: { search: "Tìm kiếm:" },
            order: [[0, 'asc']]
        });

        $(document).on('click', '.btn-select-standard', function() {
            var id = $(this).data('id');
            $('#createModal select[name="category_id"]').val(id).trigger('change');
            $('#selectStandardModal').modal('hide');
        });
    });
</script>
