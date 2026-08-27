@php
    $bag = $errors->getBag('createErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì để giá trị mặc định.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
    $oldCodes = (array) $old('classification', []);
    $oldWarnings = (array) $old('safety_warning', []);
@endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Thêm {{ $mdTitle }} Mới</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mã Danh Mục</label>
                                        <input type="text" class="form-control cat-readonly" value="{{ $nextCode }}"
                                            readonly tabindex="-1">
                                        <small class="md-sub">Sinh tự động khi lưu, không cần nhập.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Loại</label>
                                        <input type="text" name="type" maxlength="30" list="chemTypeListCreate"
                                            class="form-control {{ $bag->has('type') ? 'is-invalid' : '' }}"
                                            value="{{ $old('type') }}" placeholder="Ví dụ: Dung môi, Phụ gia...">
                                        <datalist id="chemTypeListCreate">
                                            @foreach ($types as $type)
                                                <option value="{{ $type }}"></option>
                                            @endforeach
                                        </datalist>
                                        @if ($bag->has('type'))
                                            <span class="md-error">{{ $bag->first('type') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tên Hoá Chất <span class="text-danger">*</span></label>
                                <select name="chem_names_id"
                                    class="form-control cat-select {{ $bag->has('chem_names_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn tên hoá chất --</option>
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

                            {{-- Đơn vị tính đã chuyển sang tab "Hoá Chất Của Phòng": mỗi phòng nhập /
                                 xuất theo đơn vị của phòng mình. --}}
                            <div class="form-group">
                                <label>Nhà Sản Xuất <span class="text-danger">*</span></label>
                                <select name="manufacturers_id"
                                    class="form-control cat-select {{ $bag->has('manufacturers_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn nhà sản xuất --</option>
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

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tỉ Trọng d (g/ml)</label>
                                        <input type="number" name="density" step="0.0001" min="0.0001"
                                            class="form-control {{ $bag->has('density') ? 'is-invalid' : '' }}"
                                            value="{{ $old('density') }}" placeholder="Ví dụ: 1.04">
                                        <small class="md-sub">Dùng để quy đổi giữa kg và lít.</small>
                                        @if ($bag->has('density'))
                                            <span class="md-error">{{ $bag->first('density') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Số Hồ Sơ</label>
                                        <input type="text" name="doc_no" maxlength="20"
                                            class="form-control {{ $bag->has('doc_no') ? 'is-invalid' : '' }}"
                                            value="{{ $old('doc_no') }}" placeholder="Nhập số hồ sơ">
                                        @if ($bag->has('doc_no'))
                                            <span class="md-error">{{ $bag->first('doc_no') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
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
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label>Phân Loại</label>
                                <div class="cat-check-group {{ $bag->has('classification') ? 'is-invalid' : '' }}">
                                    @foreach ($classifications as $code => $name)
                                        <label
                                            class="cat-check-item {{ in_array($code, $oldCodes) ? 'is-checked' : '' }}">
                                            <input type="checkbox" class="cat-check-input" name="classification[]"
                                                value="{{ $code }}" {{ in_array($code, $oldCodes) ? 'checked' : '' }}>
                                            <span class="cat-check-code">{{ $code }}</span>
                                            <span class="cat-check-name">{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @if ($bag->has('classification'))
                                    <span class="md-error">{{ $bag->first('classification') }}</span>
                                @endif
                                @if ($bag->has('classification.0'))
                                    <span class="md-error">{{ $bag->first('classification.0') }}</span>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>Cảnh Báo An Toàn</label>
                                <div class="cat-check-group {{ $bag->has('safety_warning') ? 'is-invalid' : '' }}">
                                    @foreach ($safetyWarnings as $code => $name)
                                        <label
                                            class="cat-check-item has-picto {{ in_array($code, $oldWarnings) ? 'is-checked' : '' }}">
                                            <input type="checkbox" class="cat-check-input" name="safety_warning[]"
                                                value="{{ $code }}" {{ in_array($code, $oldWarnings) ? 'checked' : '' }}>
                                            @include('pages.shared.safetyPictogram', ['code' => $code, 'size' => 24])
                                            <span class="cat-check-name">{{ $name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <small class="md-sub">Cho phép chọn nhiều cảnh báo. In ra dải giữa của nhãn dán lô
                                    hàng ở màn hình Nhập Hoá Chất.</small>
                                @if ($bag->has('safety_warning'))
                                    <span class="md-error">{{ $bag->first('safety_warning') }}</span>
                                @endif
                                @if ($bag->has('safety_warning.0'))
                                    <span class="md-error">{{ $bag->first('safety_warning.0') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mã danh mục sinh tự động. Không khai báo trùng tổ hợp
                        <b>Tên hoá chất - Loại - Nhà sản xuất</b>.
                        Bản ghi mới ở trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng.
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

{{-- Chia 2 cột (Phân Loại + Cảnh Báo An Toàn ở cột phải) nên cần khung rộng gấp đôi modal-lg mặc định --}}
<style>
    @media (min-width: 992px) {
        #createModal .modal-dialog {
            max-width: 1600px;
        }
    }
</style>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
