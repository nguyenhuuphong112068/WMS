@php
    $bag = $errors->getBag('dcUpdateErrors');

    // Xem chú thích ở create.blade.php: old() dùng chung cho cả 4 form của trang 2 tab.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal" id="dcUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Sửa Cấu Hình Hoá Chất Của Phòng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $old('id') }}">

                <div class="modal-body">

                    {{-- Hoá chất là khoá của dòng nên chỉ đọc: khai nhầm thì khoá dòng cũ, khai dòng mới --}}
                    <div class="form-group">
                        <label>Hoá Chất</label>
                        <input type="text" class="form-control md-readonly dc-chem-view" readonly>
                        <small class="md-sub">Không đổi được hoá chất của một dòng đã khai. Nếu khai nhầm, hãy
                            <b>khoá</b> dòng này rồi khai dòng mới để giữ lại vết.</small>
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
                            <small class="md-sub dc-shelf-hint">Để trống thì lấy hạn dùng mặc định của danh mục.</small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Ngưỡng Tồn Tối Thiểu</label>
                            <input type="number" name="min_stock" min="0" step="0.0001"
                                class="form-control {{ $bag->has('min_stock') ? 'is-invalid' : '' }}"
                                value="{{ $old('min_stock') }}" placeholder="Để trống = cảnh báo theo tỉ lệ 20%">
                            @if ($bag->has('min_stock'))
                                <span class="md-error">{{ $bag->first('min_stock') }}</span>
                            @endif
                            <small class="md-sub">Theo <b class="dc-unit-hint">đơn vị gốc</b> của danh mục.</small>
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
        $(document).on('click', '.btn-md-edit[data-modal="#dcUpdateModal"]', function() {
            var row = $(this).data('row') || {};
            var $form = $('#dcUpdateModal').find('form');

            $form.find('.dc-chem-view').val(
                (row.category_code || '') + ' - ' + (row.chem_name || '') +
                (row.unit ? ' (' + row.unit + ')' : ''));

            $form.find('.dc-shelf-hint').text(row.category_shelf_life_months ?
                'Để trống thì lấy mặc định của danh mục: ' + row.category_shelf_life_months + ' tháng.' :
                'Danh mục cũng chưa khai hạn dùng, để trống thì mã nhập sẽ không xác định được hạn nội bộ.');

            $form.find('.dc-unit-hint').text(row.unit || 'đơn vị gốc');

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.cat-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });
        });

        /* ---------- Modal Thêm mới: nhắc mặc định theo hoá chất đang chọn ---------- */
        $(document).on('change', '.dc-category', function() {
            var defaults = $(this).data('defaults') || {};
            var picked = defaults[$(this).val()];
            var $form = $(this).closest('form');

            $form.find('.dc-shelf-hint').text(!picked ? 'Để trống thì lấy hạn dùng mặc định của danh mục.' :
                picked.shelf_life_months ?
                'Để trống thì lấy mặc định của danh mục: ' + picked.shelf_life_months + ' tháng.' :
                'Danh mục cũng chưa khai hạn dùng, để trống thì mã nhập sẽ không xác định được hạn nội bộ.');

            $form.find('.dc-unit-hint').text(picked && picked.unit ? picked.unit : 'đơn vị gốc');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#dcUpdateModal').modal('show');
        });
    </script>
@endif
