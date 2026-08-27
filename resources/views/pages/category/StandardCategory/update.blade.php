@php
    $bag = $errors->getBag('updateErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì để JS đổ dữ liệu của dòng đang sửa.
    $old = fn($key, $default = null) => $bag->any() ? old($key, $default) : $default;
    $oldGroups = (array) $old('groups', []);
@endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật {{ $mdTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $old('id') }}">

                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mã Chất Chuẩn</label>
                                        <input type="text" name="code" class="form-control cat-readonly"
                                            value="{{ $old('code') }}" readonly tabindex="-1">
                                        <small class="md-sub">Mã đã cấp, không sửa được.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Version</label>
                                        <input type="text" name="version" class="form-control cat-readonly"
                                            value="{{ $old('version') }}" readonly tabindex="-1">
                                        <small class="md-sub">Version tự động cấp, không sửa được.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tên Chất Chuẩn <span class="text-danger">*</span></label>
                                <select name="chem_names_id"
                                    class="form-control cat-select {{ $bag->has('chem_names_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn tên chuẩn --</option>
                                    @foreach ($chemNames as $option)
                                        <option value="{{ $option->id }}"
                                            {{ $old('chem_names_id') == $option->id ? 'selected' : '' }}>
                                            {{ $option->name }}{{ $option->cas_no ? ' (CAS: ' . $option->cas_no . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($bag->has('chem_names_id'))
                                    <span class="md-error">{{ $bag->first('chem_names_id') }}</span>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Số CAS</label>
                                        <input type="text" name="cas_no" maxlength="50"
                                            class="form-control {{ $bag->has('cas_no') ? 'is-invalid' : '' }}"
                                            value="{{ $old('cas_no') }}" placeholder="Ví dụ: 59277-89-3">
                                        @if ($bag->has('cas_no'))
                                            <span class="md-error">{{ $bag->first('cas_no') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nguồn Gốc / Nhà Sản Xuất <span class="text-danger">*</span></label>
                                        <select name="manufacturers_id"
                                            class="form-control cat-select {{ $bag->has('manufacturers_id') ? 'is-invalid' : '' }}"
                                            required>
                                            <option value="">-- Chọn nguồn gốc / NSX --</option>
                                            @foreach ($manufacturers as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ $old('manufacturers_id') == $option->id ? 'selected' : '' }}>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($bag->has('manufacturers_id'))
                                            <span class="md-error">{{ $bag->first('manufacturers_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Đơn vị tính đã chuyển sang tab "Chất Chuẩn Của Phòng": mỗi phòng
                                 nhập / xuất theo đơn vị của phòng mình. --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tỷ Trọng (g/ml)</label>
                                        <div class="input-group">
                                            <input type="number" name="density" step="0.0001" min="0.0001"
                                                class="form-control {{ $bag->has('density') ? 'is-invalid' : '' }}"
                                                value="{{ $old('density') }}" placeholder="Ví dụ: 1.04">
                                            <div class="input-group-append">
                                                <span class="input-group-text">g/ml</span>
                                            </div>
                                        </div>
                                        @if ($bag->has('density'))
                                            <span class="md-error">{{ $bag->first('density') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Điều Kiện Bảo Quản</label>
                                        <select name="storage_condition_id"
                                            class="form-control cat-select {{ $bag->has('storage_condition_id') ? 'is-invalid' : '' }}">
                                            <option value="">-- Chọn điều kiện bảo quản --</option>
                                            @foreach ($storageConditions as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ $old('storage_condition_id') == $option->id ? 'selected' : '' }}>
                                                    {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($bag->has('storage_condition_id'))
                                            <span class="md-error">{{ $bag->first('storage_condition_id') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>



                            <div class="form-group">
                                <label>Ghi Chú</label>
                                <textarea name="note" rows="2" maxlength="500"
                                    class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ $old('note') }}</textarea>
                                @if ($bag->has('note'))
                                    <span class="md-error">{{ $bag->first('note') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="form-group">
                                <label>Phân Nhóm Chuẩn <span class="text-danger">*</span></label>
                                <div class="cat-check-group {{ $bag->has('groups') ? 'is-invalid' : '' }}">
                                    @foreach ($groups as $code => $group)
                                        <label
                                            class="cat-check-item {{ in_array($code, $oldGroups) ? 'is-checked' : '' }}">
                                            <input type="radio" class="cat-check-input" name="groups[]"
                                                value="{{ $code }}"
                                                {{ in_array($code, $oldGroups) ? 'checked' : '' }}>
                                            <span class="cat-check-code">{{ $group['no'] }}</span>
                                            <span class="cat-check-name">
                                                {{ $group['name'] }} ({{ $group['short'] }})
                                                <br><small class="md-sub">Mã trong mã ống chuẩn:
                                                    <b>{{ $group['code'] }}</b></small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @if ($bag->has('groups'))
                                    <span class="md-error">{{ $bag->first('groups') }}</span>
                                @endif
                                @if ($bag->has('groups.0'))
                                    <span class="md-error">{{ $bag->first('groups.0') }}</span>
                                @endif
                            </div>
                        </div>
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

<style>
    @media (min-width: 992px) {
        #updateModal .modal-dialog {
            max-width: 1280px;
        }
    }
</style>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
