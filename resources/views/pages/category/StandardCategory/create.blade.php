@php
    $bag = $errors->getBag('createErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì để giá trị mặc định.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
    $oldGroups = (array) $old('groups', []);

    // Số CAS điền sẵn theo tên chất chuẩn đang chọn, nhưng vẫn sửa được:
    // cùng một tên chất, chuẩn tạp có số CAS riêng.
    $casByName = $chemNames->mapWithKeys(fn ($row) => [$row->id => $row->cas_no ?: ''])->toArray();
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
                        <div class="col-lg-7">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mã Chất Chuẩn</label>
                                        <input type="text" class="form-control cat-readonly" value="{{ $nextCode }}"
                                            readonly tabindex="-1">
                                        <small class="md-sub">Sinh tự động khi lưu, dạng S00001.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Version <span class="text-danger">*</span></label>
                                        <input type="number" name="version" min="0" max="999"
                                            class="form-control {{ $bag->has('version') ? 'is-invalid' : '' }}"
                                            value="{{ $old('version', 0) }}" required>
                                        <small class="md-sub">Phiên bản do nhà sản xuất công bố, bắt đầu từ 0.</small>
                                        @if ($bag->has('version'))
                                            <span class="md-error">{{ $bag->first('version') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tên Chất Chuẩn <span class="text-danger">*</span></label>
                                <select name="chem_names_id"
                                    class="form-control cat-select sd-name-select {{ $bag->has('chem_names_id') ? 'is-invalid' : '' }}"
                                    data-cas="{{ json_encode($casByName) }}" required>
                                    <option value="">-- Chọn tên chất chuẩn --</option>
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
                                            class="form-control sd-cas-input {{ $bag->has('cas_no') ? 'is-invalid' : '' }}"
                                            value="{{ $old('cas_no') }}" placeholder="Ví dụ: 59277-89-3">
                                        <small class="md-sub">Điền sẵn theo tên đã chọn, sửa lại được.</small>
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

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Đơn Vị Tính <span class="text-danger">*</span></label>
                                        <select name="unit_id"
                                            class="form-control cat-select {{ $bag->has('unit_id') ? 'is-invalid' : '' }}"
                                            required>
                                            <option value="">-- Chọn đơn vị tính --</option>
                                            @foreach ($units as $option)
                                                <option value="{{ $option->id }}"
                                                    {{ $old('unit_id') == $option->id ? 'selected' : '' }}>
                                                    {{ $option->short_name }} - {{ $option->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($bag->has('unit_id'))
                                            <span class="md-error">{{ $bag->first('unit_id') }}</span>
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
                                    class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                                    placeholder="Ví dụ: Chuẩn gốc do VKNTP cấp, kèm phiếu công bố giá trị">{{ $old('note') }}</textarea>
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
                                                value="{{ $code }}" {{ in_array($code, $oldGroups) ? 'checked' : '' }}>
                                            <span class="cat-check-code">{{ $group['no'] }}</span>
                                            <span class="cat-check-name">
                                                {{ $group['name'] }} ({{ $group['short'] }})
                                                <br><small class="md-sub">Mã trong mã ống chuẩn:
                                                    <b>{{ $group['code'] }}</b></small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <small class="md-sub">Chỉ chọn được 1 nhóm. Lúc nhập kho, mã ống chuẩn lấy mã nhóm
                                    của nhóm được chọn.</small>
                                @if ($bag->has('groups'))
                                    <span class="md-error">{{ $bag->first('groups') }}</span>
                                @endif
                                @if ($bag->has('groups.0'))
                                    <span class="md-error">{{ $bag->first('groups.0') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mã chất chuẩn sinh tự động dạng <b>S00001</b>. Không khai báo trùng tổ hợp
                        <b>Tên chất chuẩn - Nguồn gốc/NSX - Version</b>; chất chuẩn phát hành version mới là một
                        dòng danh mục mới. Bản ghi mới ở trạng thái <b>Chờ duyệt</b>, cần được duyệt trước khi dùng.
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

{{-- Chia 2 cột (thông tin chuẩn + phân nhóm chuẩn) nên cần khung rộng hơn modal-lg mặc định --}}
<style>
    @media (min-width: 992px) {
        #createModal .modal-dialog {
            max-width: 1280px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Đổi tên chất chuẩn thì điền sẵn số CAS của tên đó, nhưng không đè giá trị đã gõ tay */
        $(document).on('change', '.sd-name-select', function() {
            var $form = $(this).closest('form');
            var $cas = $form.find('.sd-cas-input');

            if (!$cas.length || $cas.val().trim() !== '') return;

            var map = $(this).data('cas') || {};

            $cas.val(map[$(this).val()] || '');
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
