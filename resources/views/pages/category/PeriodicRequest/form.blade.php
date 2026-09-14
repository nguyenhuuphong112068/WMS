{{--
| Modal thêm / cập nhật danh sách đề nghị theo chu kỳ.
| Biến vào: $type (internal|external), $mode (create|update), $categories, $units, $departments
|
| Lịch chu kỳ: Hằng tuần / Hằng tháng / Hằng quý chọn ngày bằng ô chọn (.pr-cycle-day-select);
| "Theo số ngày" hiện ô nhập số ngày (.pr-cycle-length) và ô nhập ngày thứ mấy
| (.pr-cycle-day-input). Hai ô cycle_day luân phiên disabled nên chỉ một ô được gửi lên.
| Dòng vật tư do JS dựng từ <template class="pr-row-template"> - xem assets.blade.php.
--}}
@php
    $prRoute = 'pages.category.periodicRequest.';
    $prKey = $type === 'external' ? 'External' : 'Internal';
    $isExternal = $type === 'external';
    $isUpdate = $mode === 'update';
    $modalId = 'periodic' . $prKey . ($isUpdate ? 'Update' : 'Create') . 'Modal';
    $bag = $errors->getBag(\App\Support\MaterialPeriodicRequest::errorBag($type, $isUpdate ? 'Update' : 'Create'));
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal pr-modal" id="{{ $modalId }}" tabindex="-1" role="dialog"
    data-mode="{{ $mode }}"
    data-has-errors="{{ $bag->any() ? 1 : 0 }}"
    data-old="{{ json_encode($bag->any() ? ['cycle_day' => old('cycle_day'), 'items' => array_values((array) old('items', []))] : null) }}">
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
                        <div class="form-group col-md-7">
                            <label>Tiêu Đề <span class="text-danger">*</span></label>
                            <input type="text" name="title" maxlength="255" required
                                class="form-control {{ $bag->has('title') ? 'is-invalid' : '' }}"
                                value="{{ $old('title') }}" placeholder="VD: Vật tư bảo trì hằng tháng">
                        </div>

                        @if ($isExternal)
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
                                <label>Đối Tượng <span class="text-danger">*</span></label>
                                <select name="consumption_object_id" required
                                    class="form-control cat-select pr-object {{ $bag->has('consumption_object_id') ? 'is-invalid' : '' }}">
                                    <option value="">-- Chọn đối tượng --</option>
                                    @foreach ($objects->groupBy('type') as $objectType => $typeObjects)
                                        <optgroup label="{{ $objectTypes[$objectType] ?? $objectType }}">
                                            @foreach ($typeObjects as $object)
                                                <option value="{{ $object->id }}"
                                                    data-location="{{ $object->location }}"
                                                    data-frequency="{{ $object->frequency_label }}"
                                                    {{ (string) $old('consumption_object_id') === (string) $object->id ? 'selected' : '' }}>
                                                    {{ $object->code }} - {{ $object->name }}{{ $object->status_id != 1 ? ' (đã khoá)' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <small class="pr-object-info"></small>
                            </div>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Chu Kỳ <span class="text-danger">*</span></label>
                            <select name="periodic" required
                                class="form-control pr-periodic {{ $bag->has('periodic') ? 'is-invalid' : '' }}">
                                @foreach (\App\Support\MaterialPeriodicRequest::CYCLES as $cycleKey => $cycleLabel)
                                    <option value="{{ $cycleKey }}" {{ $old('periodic', 'month') === $cycleKey ? 'selected' : '' }}>
                                        {{ $cycleLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2 pr-length-group">
                            <label>Số Ngày Của Chu Kỳ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="cycle_length" min="1" max="{{ \App\Support\MaterialPeriodicRequest::CYCLE_MAX_LENGTH }}" step="1"
                                    class="form-control pr-cycle-length {{ $bag->has('cycle_length') ? 'is-invalid' : '' }}"
                                    value="{{ $old('cycle_length') }}" placeholder="VD: 10">
                                <div class="input-group-append"><span class="input-group-text">ngày</span></div>
                            </div>
                        </div>

                        <div class="form-group col-md-3">
                            <label>Ngày Tạo Đề Nghị Trong Chu Kỳ <span class="text-danger">*</span></label>
                            <select name="cycle_day" required
                                class="form-control pr-cycle-day-select {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}"></select>
                            <div class="input-group pr-cycle-day-input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Ngày thứ</span></div>
                                <input type="number" name="cycle_day" min="1" step="1" disabled
                                    class="form-control pr-cycle-day-input {{ $bag->has('cycle_day') ? 'is-invalid' : '' }}">
                                <div class="input-group-append"><span class="input-group-text pr-day-max">/ —</span></div>
                            </div>
                        </div>

                        <div class="form-group col-md-2">
                            <label>Ngày Bắt Đầu Chu Kỳ Đầu Tiên <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" required
                                class="form-control pr-start-date {{ $bag->has('start_date') ? 'is-invalid' : '' }}"
                                value="{{ $old('start_date', now()->toDateString()) }}">
                        </div>

                        <div class="form-group col-md-2 d-flex align-items-end">
                            <div class="pr-next-box w-100">
                                <span class="pr-next-label"><i class="far fa-calendar-alt mr-1"></i>Lần tạo kế tiếp</span>
                                <b class="pr-next-preview">—</b>
                            </div>
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
                                <col style="width: 16%">
                                <col style="width: 48px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Vật Tư</th>
                                    <th>Thông Tin Kỹ Thuật</th>
                                    <th>Số Lượng</th>
                                    <th>Đơn Vị</th>
                                    <th>Thiết Bị Liên Quan</th>
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
                                <input type="text" data-name="product_name" maxlength="255"
                                    class="form-control form-control-sm" placeholder="Thiết bị liên quan...">
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
