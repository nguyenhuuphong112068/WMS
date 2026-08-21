@php $bag = $errors->getBag('createErrors'); @endphp

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

                    <div class="form-group">
                        <label>Tên Vật Tư <span class="text-danger">*</span></label>
                        <select name="material_names_id"
                            class="form-control cat-select {{ $bag->has('material_names_id') ? 'is-invalid' : '' }}"
                            required>
                            <option value="">-- Chọn tên vật tư --</option>
                            @foreach ($materialNames as $option)
                                <option value="{{ $option->id }}"
                                    {{ old('material_names_id') == $option->id ? 'selected' : '' }}>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('material_names_id'))
                            <span class="md-error">{{ $bag->first('material_names_id') }}</span>
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
                                <label>Nhà Cung Cấp <span class="text-danger">*</span></label>
                                <select name="suppliers_id"
                                    class="form-control cat-select {{ $bag->has('suppliers_id') ? 'is-invalid' : '' }}"
                                    required>
                                    <option value="">-- Chọn nhà cung cấp --</option>
                                    @foreach ($suppliers as $option)
                                        <option value="{{ $option->id }}"
                                            {{ old('suppliers_id') == $option->id ? 'selected' : '' }}>
                                            {{ $option->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($bag->has('suppliers_id'))
                                    <span class="md-error">{{ $bag->first('suppliers_id') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Đơn Vị Tính <span class="text-danger">*</span></label>
                        <select name="unit_id"
                            class="form-control cat-select {{ $bag->has('unit_id') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Chọn đơn vị tính --</option>
                            @foreach ($units as $option)
                                <option value="{{ $option->id }}" {{ old('unit_id') == $option->id ? 'selected' : '' }}>
                                    {{ $option->short_name }} - {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('unit_id'))
                            <span class="md-error">{{ $bag->first('unit_id') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ô chọn chỉ hiển thị dữ liệu gốc <b>đã duyệt</b> và <b>đang hoạt động</b>.
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
