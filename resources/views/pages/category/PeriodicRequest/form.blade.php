{{--
| Modal thêm / cập nhật danh sách đề nghị theo chu kỳ.
| Biến vào: $type (internal|external), $mode (create|update), $categories, $units, $departments, $objects
|
| LỊCH CHU KỲ
| - Liên phòng ban: chọn Chu Kỳ tay (.pr-periodic là ô chọn); chu kỳ "Theo số ngày" / "Theo số
|   năm" hiện thêm ô độ dài (.pr-cycle-length).
| - Nội bộ: chọn Tần Suất Đề Nghị trong các tần suất của Đối tượng (.pr-frequency). JS điền
|   periodic + cycle_length vào 2 ô ẩn theo tần suất; server cũng tự suy lại từ mã tần suất.
| Ngày tạo trong chu kỳ: chu kỳ ngắn (tuần / tháng / 2 tháng / quý) chọn bằng ô chọn
| (.pr-cycle-day-select); chu kỳ dài (theo số ngày / nửa năm / năm) nhập số (.pr-cycle-day-input).
| Hai ô cycle_day luân phiên disabled nên chỉ một ô được gửi lên.
| Nội bộ: .pr-day-mode = cal_due ("Theo ngày đến hạn lịch CAL") thì ẩn + disabled cả hai ô cycle_day,
| ngày tạo theo Sch_DueDate lịch Pending CAL của đối tượng (.pr-cal-info hiện hạn đang theo) - xem
| MaterialPeriodicRequest::calSchedulePreview().
|
| Dòng vật tư do JS dựng từ <template class="pr-row-template"> - xem assets.blade.php.
--}}
@php
    $prSupport = \App\Support\MaterialPeriodicRequest::class;
    $prRoute = 'pages.category.periodicRequest.';
    $prKey = $type === 'external' ? 'External' : 'Internal';
    $isExternal = $type === 'external';
    $isUpdate = $mode === 'update';
    $modalId = 'periodic' . $prKey . ($isUpdate ? 'Update' : 'Create') . 'Modal';
    $bag = $errors->getBag($prSupport::errorBag($type, $isUpdate ? 'Update' : 'Create'));
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal pr-modal" id="{{ $modalId }}" tabindex="-1" role="dialog"
    data-mode="{{ $mode }}"
    data-has-errors="{{ $bag->any() ? 1 : 0 }}"
    data-old="{{ json_encode($bag->any() ? [
        'cycle_day' => old('cycle_day'),
        'cycle_day_mode' => old('cycle_day_mode'),
        'frequency' => old('frequency'),
        'items' => array_values((array) old('items', [])),
    ] : null) }}">
    <div class="modal-dialog pr-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-{{ $isExternal ? 'exchange-alt' : 'sync-alt' }}"></i>
                    {{ $isUpdate ? 'Cập Nhật' : 'Thêm' }} Danh Sách Đề Nghị {{ $isExternal ? 'Liên Phòng Ban' : 'Nội Bộ' }} Theo Chu Kỳ
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($prRoute . ($isUpdate ? 'update' : 'store')) }}" method="POST" class="pr-form">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                @if ($isUpdate)
                    <input type="hidden" name="id" value="{{ $old('id') }}">
                @endif

                <div class="modal-body">
                    @if ($bag->any())
                        <div class="alert alert-danger pr-errors">
                            <ul class="mb-0 pl-3">
                                @foreach (array_unique($bag->all()) as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-row">
                        @if ($isExternal)
                            <div class="form-group col-md-7">
                                <label>Tiêu Đề <span class="text-danger">*</span></label>
                                <input type="text" name="title" maxlength="255" required
                                    class="form-control {{ $bag->has('title') ? 'is-invalid' : '' }}"
                                    value="{{ $old('title') }}" placeholder="VD: Vật tư bảo trì hằng tháng">
                            </div>

                            <div class="form-group col-md-5">
                                <label>Phòng Cấp Phát <span class="text-danger">*</span></label>
                                <select name="to_department_id" required
                                    class="form-control cat-select {{ $bag->has('to_department_id') ? 'is-invalid' : '' }}">
                                    <option value="">-- Chọn phòng cấp phát --</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            {{ (string) $old('to_department_id') === (string) $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}{{ $department->shortName ? ' (' . $department->shortName . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            @php $objectTypes = \App\Http\Controllers\Pages\MaterData\ConsumptionObjectController::typeLabels(); @endphp
                            <div class="form-group col-md-5">
                                <label>Đối Tượng</label>
                                <div class="input-group pr-object-group">
                                    <select name="consumption_object_id"
                                        class="form-control cat-select pr-object {{ $bag->has('consumption_object_id') ? 'is-invalid' : '' }}">
                                        <option value="">-- Chọn đối tượng --</option>
                                        @foreach ($objects->groupBy('type') as $objectType => $typeObjects)
                                            <optgroup label="{{ $objectTypes[$objectType] ?? $objectType }}">
                                                @foreach ($typeObjects as $object)
                                                    <option value="{{ $object->id }}"
                                                        data-code="{{ $object->code }}"
                                                        data-name="{{ $object->name }}"
                                                        data-location="{{ $object->location }}"
                                                        data-frequency="{{ $object->frequency_label }}"
                                                        data-frequencies="{{ $object->frequency }}"
                                                        {{ (string) $old('consumption_object_id') === (string) $object->id ? 'selected' : '' }}>
                                                        {{ $object->code }} - {{ $object->name }}{{ $object->status_id != 1 ? ' (đã khoá)' : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-info pr-open-object-picker"
                                            data-picker="#periodicInternalObjectPickerModal" title="Mở dữ liệu gốc đối tượng">
                                            <i class="fas fa-crosshairs"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="pr-object-info"></small>
                            </div>

                            <div class="form-group col-md-7">
                                <label>Tiêu Đề <span class="text-danger">*</span></label>
                                <input type="text" name="title" maxlength="255" required
                                    class="form-control {{ $bag->has('title') ? 'is-invalid' : '' }}"
                                    value="{{ $old('title') }}" placeholder="VD: Vật tư bảo trì hằng tháng">
                            </div>
                        @endif
                    </div>

                    <div class="form-row pr-cycle-row">
                        @if ($isExternal)
                            <div class="form-group col-md-3">
                                <label>Chu Kỳ <span class="text-danger">*</span></label>
                                <select name="periodic" required
                                    class="form-control pr-periodic {{ $bag->has('periodic') ? 'is-invalid' : '' }}">
                                    @foreach ($prSupport::CYCLES as $cycleKey => $cycleLabel)
                                        <option value="{{ $cycleKey }}" {{ $old('periodic', 'month') === $cycleKey ? 'selected' : '' }}>
                                            {{ $cycleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-2 pr-length-group">
                                <label>Số <span class="pr-length-name">Ngày</span> Của Chu Kỳ <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="cycle_length" min="1" max="{{ $prSupport::CYCLE_MAX_LENGTH }}" step="1"
                                        class="form-control pr-cycle-length {{ $bag->has('cycle_length') ? 'is-invalid' : '' }}"
                                        value="{{ $old('cycle_length') }}" placeholder="VD: 10">
                                    <div class="input-group-append"><span class="input-group-text pr-length-unit">ngày</span></div>
                                </div>
                            </div>
                        @else
                            <div class="form-group col-md-3">
                                <label>Tần Suất Đề Nghị <span class="text-danger">*</span></label>
                                <select name="frequency" required
                                    class="form-control pr-frequency {{ $bag->has('frequency') ? 'is-invalid' : '' }}">
                                    <option value="">-- Chọn đối tượng trước --</option>
                                </select>
                                {{-- Lịch suy từ tần suất, JS điền để dựng ô ngày + xem trước; server tự tính lại --}}
                                <input type="hidden" name="periodic" class="pr-periodic" value="{{ $old('periodic') }}">
                                <input type="hidden" name="cycle_length" class="pr-cycle-length" value="{{ $old('cycle_length') }}">
                            </div>
                        @endif

                        <div class="form-group col-md-3">
                            <label>Ngày Tạo Đề Nghị Trong Chu Kỳ <span class="text-danger">*</span></label>
                            @if ($isExternal)
                                <select name="cycle_day" required
                                    class="form-control pr-cycle-day-select {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}"></select>
                                <div class="input-group pr-cycle-day-input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Ngày thứ</span></div>
                                    <input type="number" name="cycle_day" min="1" step="1" disabled
                                        class="form-control pr-cycle-day-input {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}">
                                    <div class="input-group-append"><span class="input-group-text pr-day-max">/ —</span></div>
                                </div>
                            @else
                                {{--
                                | Theo hạn lịch CAL: chỉ bật khi đối tượng đồng bộ từ CAL và có lịch Pending của tần suất
                                | đang chọn (JS hỏi pages.category.periodicRequest.calSchedule). Chọn CAL thì cột "Ngày Cụ
                                | Thể" ẩn đi, cột "Tạo Trước" hiện ra thay vào (JS prApplyCycle) - ngày tạo = Sch_DueDate
                                | của lịch CAL trừ đi số ngày đó.
                                --}}
                                <select name="cycle_day_mode"
                                    class="form-control pr-day-mode {{ $bag->has('cycle_day_mode') ? 'is-invalid' : '' }}">
                                    <option value="{{ $prSupport::DAY_MODE_FIXED }}">Ngày cố định trong chu kỳ</option>
                                    <option value="{{ $prSupport::DAY_MODE_CAL_DUE }}" disabled>Theo ngày đến hạn lịch CAL</option>
                                </select>
                                <small class="pr-cal-info"></small>
                            @endif
                        </div>

                        @unless ($isExternal)
                            {{-- Chỉ hiện khi đang chọn "Ngày cố định trong chu kỳ" - JS prApplyCycle ẩn hẳn cột này ở chế độ CAL --}}
                            <div class="form-group col-md-2 pr-day-col">
                                <label>Ngày Cụ Thể</label>
                                <select name="cycle_day" required
                                    class="form-control pr-cycle-day-select {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}"></select>
                                <div class="input-group pr-cycle-day-input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Ngày thứ</span></div>
                                    <input type="number" name="cycle_day" min="1" step="1" disabled
                                        class="form-control pr-cycle-day-input {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}">
                                    <div class="input-group-append"><span class="input-group-text pr-day-max">/ —</span></div>
                                </div>
                            </div>

                            {{-- Ngược lại "Ngày Cụ Thể": chỉ hiện khi đang chọn "Theo ngày đến hạn lịch CAL" --}}
                            <div class="form-group col-md-2 pr-lead-col">
                                <label>Tạo Trước</label>
                                <div class="input-group">
                                    <input type="number" name="cal_lead_days" min="0" max="{{ $prSupport::CAL_LEAD_DAYS_MAX }}" step="1"
                                        value="{{ $old('cal_lead_days', $prSupport::CAL_LEAD_DAYS_DEFAULT) }}"
                                        class="form-control pr-cal-lead-days {{ $bag->has('cal_lead_days') ? 'is-invalid' : '' }}">
                                    <div class="input-group-append"><span class="input-group-text">ngày</span></div>
                                </div>
                            </div>
                        @endunless

                        @if ($isExternal || $isUpdate)
                            <div class="form-group col-md-2">
                                <label>Ngày Bắt Đầu Chu Kỳ Đầu Tiên <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" required
                                    class="form-control pr-start-date {{ $bag->has('start_date') ? 'is-invalid' : '' }}"
                                    value="{{ $old('start_date', now()->toDateString()) }}">
                            </div>
                        @else
                            {{--
                            | Nội bộ, lúc thêm mới: bỏ hẳn ô chọn - luôn bắt đầu từ ngày tạo danh sách, đỡ phải
                            | chọn. Cần dời lại (VD tạo nhầm ngày) thì sửa danh sách sau khi tạo, modal Sửa vẫn
                            | còn ô này.
                            --}}
                            <input type="hidden" name="start_date" class="pr-start-date" value="{{ now()->toDateString() }}">
                        @endif

                        {{-- Không đặt col-md cố định - tự giãn lấp phần còn lại của hàng, kể cả khi cột "Ngày Cụ Thể" ẩn --}}
                        <div class="form-group pr-next-col">
                            <label><i class="far fa-calendar-alt mr-1"></i>Lần Tạo Kế Tiếp</label>
                            <div class="pr-next-box"><b class="pr-next-preview">—</b></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-2" style="gap: 6px">
                        <label class="mb-0">Vật Tư Đề Nghị <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap" style="gap: 6px">
                            <button type="button" class="btn btn-sm btn-outline-info pr-open-picker"
                                data-picker="#periodic{{ $prKey }}PickerModal">
                                <i class="fas fa-th-list mr-1"></i> {{ $isExternal ? 'Mở danh mục công ty' : 'Mở danh mục phòng' }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary pr-add-row">
                                <i class="fas fa-plus mr-1"></i> Thêm vật tư
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive pr-rows-wrap {{ $bag->has('items') ? 'is-invalid' : '' }}">
                        <table class="table table-bordered table-sm mb-0 pr-rows-table" data-no-datatable>
                            {{-- Bề rộng cố định từng cột: tên vật tư dài không được ép hẹp cột Số Lượng / Đơn Vị --}}
                            <colgroup>
                                <col>
                                <col style="width: 17%">
                                <col style="width: 120px">
                                <col style="width: 110px">
                                <col style="width: 16%">
                                <col style="width: 48px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Vật Tư</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Số Lượng</th>
                                    <th>Đơn Vị</th>
                                    <th>Mục Đích Sử Dụng</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="pr-rows"></tbody>
                        </table>
                    </div>

                    <template class="pr-row-template">
                        <tr>
                            <td>
                                <select data-name="category_id" class="form-control form-control-sm pr-cat" required>
                                    <option value="">-- Chọn vật tư --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" data-unit="{{ $category->unit_short_name }}"
                                            data-spec="{{ $category->technical_specification }}">
                                            {{ $category->code }} — {{ $category->material_name }}{{ $category->manufacturer_short_name ? ' (' . $category->manufacturer_short_name . ')' : '' }}{{ $category->technical_specification ? ' · ' . $category->technical_specification : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                {{-- Chỉ hiển thị, lấy theo vật tư đang chọn - không gửi lên server --}}
                                <input type="text" class="form-control form-control-sm pr-spec" readonly tabindex="-1" placeholder="—">
                            </td>
                            <td>
                                <input type="text" inputmode="decimal" data-name="requested_amount" required
                                    class="form-control form-control-sm text-right js-decimal" placeholder="0">
                            </td>
                            <td>
                                <select data-name="requested_unit" class="form-control form-control-sm pr-unit">
                                    <option value="">--</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->short_name }}">{{ $unit->short_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" data-name="purpose" maxlength="500"
                                    class="form-control form-control-sm" placeholder="Mục đích sử dụng...">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-danger pr-del-row" title="Xoá dòng">&times;</button>
                            </td>
                        </tr>
                    </template>

                    @if ($isUpdate)
                        <div class="form-group mt-3 mb-0">
                            <label>Lý Do Điều Chỉnh <span class="text-danger">*</span></label>
                            <textarea name="change_reason" rows="2" maxlength="500" required
                                class="form-control {{ $bag->has('change_reason') ? 'is-invalid' : '' }}"
                                placeholder="Nêu rõ lý do sửa danh sách này">{{ $old('change_reason') }}</textarea>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> {{ $isUpdate ? 'Cập nhật' : 'Lưu danh sách' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
