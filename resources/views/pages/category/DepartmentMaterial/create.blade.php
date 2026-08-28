@php
    $bag = $errors->getBag('dmCreateErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì để trống.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal" id="dmCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Khai Vật Tư Cho Phòng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <div class="form-group">
                        <label>Vật Tư <span class="text-danger">*</span></label>
                        <select name="category_id"
                            class="form-control cat-select {{ $bag->has('category_id') ? 'is-invalid' : '' }}" required>
                            <option value="">-- Chọn vật tư --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ $old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->material_name }}
                                    @if ($category->manufacturer_short_name)
                                        ({{ $category->manufacturer_short_name }})
                                    @elseif ($category->manufacturer_name)
                                        ({{ $category->manufacturer_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('category_id'))
                            <span class="md-error">{{ $bag->first('category_id') }}</span>
                        @endif
                        <small class="md-sub">Chỉ hiện vật tư đã duyệt trong danh mục chung và
                            <b>phòng chưa khai</b>. Danh sách trống nghĩa là phòng đã khai hết.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Phân Loại</label>
                            <select name="classification_id"
                                class="form-control cat-select {{ $bag->has('classification_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa phân loại --</option>
                                @foreach ($classifications as $classification)
                                    <option value="{{ $classification->id }}"
                                        {{ $old('classification_id') == $classification->id ? 'selected' : '' }}>
                                        {{ $classification->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('classification_id'))
                                <span class="md-error">{{ $bag->first('classification_id') }}</span>
                            @endif
                            <small class="md-sub">Bộ phân loại khai ở <b>Dữ Liệu Gốc → Phân Loại Vật Tư</b>.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Đơn Vị Tính <span class="text-danger">*</span></label>
                            <select name="unit_id"
                                class="form-control cat-select {{ $bag->has('unit_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn đơn vị tính --</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" data-short="{{ $unit->short_name }}"
                                        {{ $old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->short_name }} - {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('unit_id'))
                                <span class="md-error">{{ $bag->first('unit_id') }}</span>
                            @endif
                            <small class="md-sub">Đơn vị phòng dùng để nhập / xuất và ghi tồn cho vật tư này.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ngưỡng Tồn Tối Thiểu</label>
                        <input type="number" name="min_stock" min="0" step="0.0001"
                            class="form-control dm-min-stock {{ $bag->has('min_stock') ? 'is-invalid' : '' }}"
                            value="{{ $old('min_stock') }}" placeholder="Để trống nếu chưa đặt ngưỡng">
                        @if ($bag->has('min_stock'))
                            <span class="md-error">{{ $bag->first('min_stock') }}</span>
                        @endif
                        <small class="md-sub">Tồn xuống dưới mức này thì coi là sắp hết. Theo
                            <b class="dm-unit-hint">đơn vị</b> đã chọn ở trên.</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: chỉ dùng cho tổ Hoá Lý">{{ $old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Bản chất vật tư (tên, nhà sản xuất, thông tin kỹ thuật) khai ở
                        <b>Danh Mục Vật Tư Công Ty</b> và dùng chung toàn công ty. Ở đây chỉ khai phần
                        <b>riêng của phòng</b>.
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /* Dòng nhắc "ngưỡng tồn theo đơn vị nào" chạy theo ô Đơn Vị Tính */
        $(document).on('change', '#dmCreateModal .cat-select[name="unit_id"], #dmUpdateModal .cat-select[name="unit_id"]',
            function() {
                var short = $(this).find('option:selected').data('short');
                $(this).closest('form').find('.dm-unit-hint').text(short || 'đơn vị');
            });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#dmCreateModal').modal('show');
        });
    </script>
@endif
