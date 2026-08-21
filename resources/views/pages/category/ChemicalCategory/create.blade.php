@php
    $bag = $errors->getBag('createErrors');
    $oldCodes = (array) old('classification', []);
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mã Danh Mục</label>
                                <input type="text" class="form-control cat-readonly" value="{{ $nextCode }}" readonly
                                    tabindex="-1">
                                <small class="md-sub">Sinh tự động khi lưu, không cần nhập.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Loại</label>
                                <input type="text" name="type" maxlength="30" list="chemTypeListCreate"
                                    class="form-control {{ $bag->has('type') ? 'is-invalid' : '' }}"
                                    value="{{ old('type') }}" placeholder="Ví dụ: Dung môi, Phụ gia...">
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
                                    {{ old('chem_names_id') == $option->id ? 'selected' : '' }}>
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
                                <label>Nhà Sản Xuất <span class="text-danger">*</span></label>
                                <select name="manufacturers_id"
                                    class="form-control cat-select {{ $bag->has('manufacturers_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn nhà sản xuất --</option>
                                    @foreach ($manufacturers as $option)
                                        <option value="{{ $option->id }}"
                                            {{ old('manufacturers_id') == $option->id ? 'selected' : '' }}>
                                            {{ $option->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($bag->has('manufacturers_id'))
                                    <span class="md-error">{{ $bag->first('manufacturers_id') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Đơn Vị Tính <span class="text-danger">*</span></label>
                                <select name="unit_id"
                                    class="form-control cat-select {{ $bag->has('unit_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn đơn vị tính --</option>
                                    @foreach ($units as $option)
                                        <option value="{{ $option->id }}"
                                            {{ old('unit_id') == $option->id ? 'selected' : '' }}>
                                            {{ $option->short_name }} - {{ $option->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($bag->has('unit_id'))
                                    <span class="md-error">{{ $bag->first('unit_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tỉ Trọng d (g/ml)</label>
                                <input type="number" name="density" step="0.0001" min="0.0001"
                                    class="form-control {{ $bag->has('density') ? 'is-invalid' : '' }}"
                                    value="{{ old('density') }}" placeholder="Ví dụ: 1.04">
                                <small class="md-sub">Dùng để quy đổi giữa kg và lít.</small>
                                @if ($bag->has('density'))
                                    <span class="md-error">{{ $bag->first('density') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Số Tài Liệu</label>
                                <input type="text" name="doc_no" maxlength="20"
                                    class="form-control {{ $bag->has('doc_no') ? 'is-invalid' : '' }}"
                                    value="{{ old('doc_no') }}" placeholder="Nhập số tài liệu">
                                @if ($bag->has('doc_no'))
                                    <span class="md-error">{{ $bag->first('doc_no') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Hạn Dùng Mặc Định</label>
                                <div class="input-group">
                                    <input type="number" name="shelf_life_months" min="1" max="1200"
                                        class="form-control {{ $bag->has('shelf_life_months') ? 'is-invalid' : '' }}"
                                        value="{{ old('shelf_life_months') }}" placeholder="Ví dụ: 24">
                                    <div class="input-group-append">
                                        <span class="input-group-text">tháng</span>
                                    </div>
                                </div>
                                <small class="md-sub">Để tự tính ngày hết hạn khi nhập lô.</small>
                                @if ($bag->has('shelf_life_months'))
                                    <span class="md-error">{{ $bag->first('shelf_life_months') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Điều Kiện Bảo Quản</label>
                                <select name="storage_condition_id"
                                    class="form-control cat-select {{ $bag->has('storage_condition_id') ? 'is-invalid' : '' }}">
                                    <option value="">-- Chọn điều kiện bảo quản --</option>
                                    @foreach ($storageConditions as $option)
                                        <option value="{{ $option->id }}"
                                            {{ old('storage_condition_id') == $option->id ? 'selected' : '' }}>
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
                        <label>Phân Loại</label>
                        <div class="cat-check-group {{ $bag->has('classification') ? 'is-invalid' : '' }}">
                            @foreach ($classifications as $code => $name)
                                <label class="cat-check-item {{ in_array($code, $oldCodes) ? 'is-checked' : '' }}">
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

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
