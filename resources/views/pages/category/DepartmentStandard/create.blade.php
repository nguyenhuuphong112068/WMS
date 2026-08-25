@php
    $bag = $errors->getBag('dsCreateErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì lấy giá trị mặc định.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;

    // Hạn dùng mặc định của từng chất chuẩn, JS đổ vào dòng nhắc khi đổi ô chọn
    $dsDefaults = $categories
        ->mapWithKeys(
            fn($category) => [
                $category->id => [
                    'shelf_life_months' => $category->shelf_life_months,
                    'unit' => $category->unit_short_name ?: '',
                ],
            ],
        )
        ->toArray();
@endphp

<div class="modal fade md-modal" id="dsCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Khai Chất Chuẩn Cho Phòng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'store') }}" method="POST">
                @csrf

                <div class="modal-body">

                    <div class="form-group">
                        <label>Chất Chuẩn <span class="text-danger">*</span></label>
                        <select name="category_id"
                            class="form-control cat-select ds-category {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                            data-defaults="{{ json_encode($dsDefaults) }}" required>
                            <option value="">-- Chọn chất chuẩn --</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ $old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->code }} - {{ $category->standard_name }}
                                    (v{{ $category->version }}{{ $category->unit_short_name ? ', ' . $category->unit_short_name : '' }})
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('category_id'))
                            <span class="md-error">{{ $bag->first('category_id') }}</span>
                        @endif
                        <small class="md-sub">Chỉ hiện chất chuẩn đã duyệt trong danh mục chung và
                            <b>phòng chưa khai</b>. Danh sách trống nghĩa là phòng đã khai hết.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Hạn Dùng Nội Bộ (tháng)</label>
                            <input type="number" name="shelf_life_months" min="1" max="1200" step="1"
                                class="form-control {{ $bag->has('shelf_life_months') ? 'is-invalid' : '' }}"
                                value="{{ $old('shelf_life_months') }}" placeholder="Để trống = theo danh mục">
                            @if ($bag->has('shelf_life_months'))
                                <span class="md-error">{{ $bag->first('shelf_life_months') }}</span>
                            @endif
                            <small class="md-sub ds-shelf-hint">Hạn tính từ ngày mở ống. Để trống thì lấy hạn dùng
                                mặc định của danh mục.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngưỡng Tồn Tối Thiểu</label>
                            <input type="number" name="min_stock" min="0" step="0.0001"
                                class="form-control {{ $bag->has('min_stock') ? 'is-invalid' : '' }}"
                                value="{{ $old('min_stock') }}" placeholder="Để trống = cảnh báo theo tỉ lệ 20%">
                            @if ($bag->has('min_stock'))
                                <span class="md-error">{{ $bag->first('min_stock') }}</span>
                            @endif
                            <small class="md-sub">Tồn xuống dưới mức này thì màn hình Tồn Kho báo "Sắp hết".
                                Theo <b class="ds-unit-hint">đơn vị gốc</b> của danh mục.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Vị Trí Lưu Trữ Quy Hoạch</label>
                            <select name="default_location_id"
                                class="form-control cat-select {{ $bag->has('default_location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa quy hoạch --</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ $old('default_location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->warehouse_name ?: '—' }} /
                                        {{ $location->room_name ?: '—' }} /
                                        {{ $location->shelf_name ?: '—' }} /
                                        {{ $location->name }} ({{ $location->code }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('default_location_id'))
                                <span class="md-error">{{ $bag->first('default_location_id') }}</span>
                            @endif
                            <small class="md-sub">Chỗ <b>dự kiến</b> để ống chuẩn. Vị trí thật của từng ống vẫn khai
                                lúc nhập.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Điều Kiện Bảo Quản</label>
                            <select name="storage_condition_id"
                                class="form-control cat-select {{ $bag->has('storage_condition_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Theo danh mục --</option>
                                @foreach ($storageConditions as $condition)
                                    <option value="{{ $condition->id }}"
                                        {{ $old('storage_condition_id') == $condition->id ? 'selected' : '' }}>
                                        {{ $condition->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('storage_condition_id'))
                                <span class="md-error">{{ $bag->first('storage_condition_id') }}</span>
                            @endif
                            <small class="md-sub">Chỉ chọn khi phòng bảo quản khác với danh mục.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2" class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: chỉ dùng cho định lượng HPLC">{{ $old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Bản chất chất chuẩn (tên, số CAS, nguồn gốc, version, phân nhóm chuẩn, đơn vị gốc) khai ở
                        <b>Danh Mục Chất Chuẩn</b> và dùng chung toàn công ty. Ở đây chỉ khai phần
                        <b>riêng của phòng</b>. Để trống ô nào thì ô đó tự chạy theo danh mục.
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
            $('#dsCreateModal').modal('show');
        });
    </script>
@endif
