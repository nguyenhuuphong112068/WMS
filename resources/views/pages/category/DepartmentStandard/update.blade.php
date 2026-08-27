@php
    $bag = $errors->getBag('dsUpdateErrors');

    // Trang có 2 tab nên có tới 4 form dùng chung kho old(): chỉ điền lại giá trị vừa
    // nhập khi chính form này báo lỗi, không thì để JS đổ dữ liệu của dòng đang sửa.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal" id="dsUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Cập Nhật {{ $mdTitle }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $old('id') }}">

                <div class="modal-body">

                    <div class="form-group">
                        <label>Chất Chuẩn</label>
                        <input type="text" class="form-control cat-readonly ds-standard-view" readonly tabindex="-1"
                            value="">
                        <small class="md-sub">Không đổi được chất chuẩn của một dòng đã khai. Khai nhầm thì khoá
                            dòng này rồi khai dòng mới, để giữ vết.</small>
                    </div>

                    {{-- Đơn vị tính là của RIÊNG PHÒNG: danh mục chung không còn khai đơn vị --}}
                    <div class="form-group">
                        <label>Đơn Vị Tính <span class="text-danger">*</span></label>
                        <select name="unit_id"
                            class="form-control cat-select ds-unit {{ $bag->has('unit_id') ? 'is-invalid' : '' }}"
                            required>
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
                        <small class="md-sub">Đổi đơn vị chỉ đổi cách khai từ nay về sau, số liệu các phiếu đã
                            lưu giữ nguyên.</small>
                    </div>

                    @include('pages.category.shared.unitConversion', [
                        'prefix' => 'ds',
                        'bag' => $bag,
                        'unitsInUse' => $unitsInUse,
                        'conversions' => $conversions,
                        'label' => 'chất chuẩn',
                    ])

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Hạn Dùng Nội Bộ (tháng)</label>
                            <input type="number" name="shelf_life_months" min="1" max="1200" step="1"
                                class="form-control {{ $bag->has('shelf_life_months') ? 'is-invalid' : '' }}"
                                value="{{ $old('shelf_life_months') }}" placeholder="Để trống = theo danh mục">
                            @if ($bag->has('shelf_life_months'))
                                <span class="md-error">{{ $bag->first('shelf_life_months') }}</span>
                            @endif
                            <small class="md-sub ds-shelf-hint">Để trống thì lấy hạn dùng mặc định của danh
                                mục.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngưỡng Tồn Tối Thiểu</label>
                            <input type="number" name="min_stock" min="0" step="0.0001"
                                class="form-control {{ $bag->has('min_stock') ? 'is-invalid' : '' }}"
                                value="{{ $old('min_stock') }}" placeholder="Để trống = cảnh báo theo tỉ lệ 20%">
                            @if ($bag->has('min_stock'))
                                <span class="md-error">{{ $bag->first('min_stock') }}</span>
                            @endif
                            <small class="md-sub">Theo <b class="ds-unit-hint">đơn vị</b> đã chọn ở trên.</small>
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
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi Chú</label>
                        <textarea name="note" rows="2"
                            class="form-control {{ $bag->has('note') ? 'is-invalid' : '' }}">{{ $old('note') }}</textarea>
                        @if ($bag->has('note'))
                            <span class="md-error">{{ $bag->first('note') }}</span>
                        @endif
                    </div>

                    <div class="md-hint">
                        <i class="fas fa-info-circle mr-1"></i>
                        Để trống ô nào thì ô đó tự chạy theo <b>Danh Mục Chất Chuẩn</b>. Cấu hình này chỉ áp dụng cho
                        phòng ban <b>{{ session('user')['selected_department'] }}</b>.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ---------- Ô chỉ đọc + dòng nhắc mặc định, đổ theo dòng đang sửa ---------- */
        $(document).on('click', '.btn-md-edit[data-modal="#dsUpdateModal"]', function() {
            var row = $(this).data('row') || {};
            var $form = $('#dsUpdateModal').find('form');

            $form.find('.ds-standard-view').val(
                (row.category_code || '') + ' - ' + (row.standard_name || '') +
                (row.version !== undefined && row.version !== null ? ' (v' + row.version + ')' : ''));

            $form.find('.ds-shelf-hint').text(row.category_shelf_life_months ?
                'Để trống thì lấy mặc định của danh mục: ' + row.category_shelf_life_months + ' tháng.' :
                'Danh mục cũng chưa khai hạn dùng, để trống thì ống chuẩn sẽ không xác định được hạn nội bộ.');

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.cat-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });
        });

        /* ---------- Modal Thêm mới: nhắc mặc định theo chất chuẩn đang chọn ---------- */
        $(document).on('change', '.ds-category', function() {
            var defaults = $(this).data('defaults') || {};
            var picked = defaults[$(this).val()];
            var $form = $(this).closest('form');

            $form.find('.ds-shelf-hint').text(!picked ?
                'Hạn tính từ ngày mở ống. Để trống thì lấy hạn dùng mặc định của danh mục.' :
                picked.shelf_life_months ?
                'Để trống thì lấy mặc định của danh mục: ' + picked.shelf_life_months + ' tháng.' :
                'Danh mục cũng chưa khai hạn dùng, để trống thì ống chuẩn sẽ không xác định được hạn nội bộ.');
        });

        /* ---------- Dòng nhắc "ngưỡng tồn theo đơn vị nào" chạy theo ô Đơn Vị Tính ---------- */
        $(document).on('change', '.ds-unit', function() {
            var short = $(this).find('option:selected').data('short');

            $(this).closest('form').find('.ds-unit-hint').text(short || 'đơn vị');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#dsUpdateModal').modal('show');
        });
    </script>
@endif
