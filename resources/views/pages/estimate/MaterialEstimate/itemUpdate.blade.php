@php
    $bag = $errors->getBag('itemUpdateErrors');
    $estSource = old('source', 'category');
@endphp

{{--
| Modal sửa một vật tư dự trù. Phần lớn dữ liệu do JS dùng chung đổ vào khi bấm nút Sửa
| (xem pages/estimate/shared/assets.blade.php); riêng ô material_name không có bên dự trù
| hoá chất nên đổ thêm ở cuối file.
--}}
<div class="modal fade md-modal" id="itemUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật Vật Tư Dự Trù</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($estRoute . 'updateItem') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">

                <div class="modal-body">

                    <div class="form-group">
                        <label>Nguồn Vật Tư <span class="text-danger">*</span></label>
                        <div>
                            <label class="est-switch">
                                <input type="radio" name="source" value="category" {{ $estSource === 'category' ? 'checked' : '' }}>
                                <span>Chọn trong Danh Mục Vật Tư</span>
                            </label>
                            <label class="est-switch">
                                <input type="radio" name="source" value="manual" {{ $estSource === 'manual' ? 'checked' : '' }}>
                                <span>Vật tư chưa có trong danh mục</span>
                            </label>
                        </div>
                        @if ($bag->has('source')) <span class="md-error">{{ $bag->first('source') }}</span> @endif
                    </div>

                    <div class="form-group est-source-category">
                        <label>Vật Tư Trong Danh Mục <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-control est-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn vật tư --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->material_name }} — {{ $category->manufacturer_short_name }}
                                    @if ($category->technical_specification) ({{ $category->technical_specification }}) @endif
                                    {{ $category->unit_short_name ? ' · ' . $category->unit_short_name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('category_id')) <span class="md-error">{{ $bag->first('category_id') }}</span> @endif
                    </div>

                    <div class="form-group est-source-manual">
                        <label>Tên Vật Tư <span class="text-danger">*</span></label>
                        <input type="text" name="material_name" maxlength="255"
                            class="form-control {{ $bag->has('material_name') ? 'is-invalid' : '' }}" value="{{ old('material_name') }}">
                        @if ($bag->has('material_name')) <span class="md-error">{{ $bag->first('material_name') }}</span> @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Thông Tin Kỹ Thuật</label>
                            <textarea name="technical_information" rows="3" maxlength="1000"
                                class="form-control {{ $bag->has('technical_information') ? 'is-invalid' : '' }}">{{ old('technical_information') }}</textarea>
                            @if ($bag->has('technical_information')) <span class="md-error">{{ $bag->first('technical_information') }}</span> @endif
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mục Đích Sử Dụng</label>
                            <textarea name="purpose" rows="3" maxlength="1000"
                                class="form-control {{ $bag->has('purpose') ? 'is-invalid' : '' }}">{{ old('purpose') }}</textarea>
                            @if ($bag->has('purpose')) <span class="md-error">{{ $bag->first('purpose') }}</span> @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ngày Mong Muốn Giao</label>
                        <input type="date" name="expected_delivery_date"
                            class="form-control w-25 {{ $bag->has('expected_delivery_date') ? 'is-invalid' : '' }}"
                            value="{{ old('expected_delivery_date') }}">
                        @if ($bag->has('expected_delivery_date')) <span class="md-error">{{ $bag->first('expected_delivery_date') }}</span> @endif
                    </div>

                    <div class="form-group">
                        <label>Số Lượng Dự Trù Theo Tháng <span class="text-danger">*</span></label>
                        @include('pages.estimate.shared.amountsBox', [
                            'units' => $units,
                            'oldRows' => (array) old('amounts', []),
                        ])
                        @foreach ($bag->keys() as $estKey)
                            @if (str_starts_with($estKey, 'amounts'))
                                <span class="md-error">{{ $bag->first($estKey) }}</span>
                            @endif
                        @endforeach
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Các dòng số lượng được <b>ghi lại toàn bộ</b> mỗi lần lưu: dòng nào bị bớt đi là mất khỏi phiếu.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // JS dùng chung của nhóm Dự Trù chỉ biết ô chem_name của dự trù hoá chất; ở đây ô
        // tên là material_name nên đổ thêm sau handler dùng chung.
        $(document).on('click', '.btn-est-item-edit', function() {
            var row = $(this).data('row') || {};
            $('#itemUpdateModal').find('[name="material_name"]').val(row.material_name || '');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() { $('#itemUpdateModal').modal('show'); });
    </script>
@endif
