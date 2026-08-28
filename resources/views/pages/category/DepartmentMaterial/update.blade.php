@php
    $bag = $errors->getBag('dmUpdateErrors');

    // Xem chú thích ở create.blade.php: old() dùng chung cho cả 4 form của trang 2 tab.
    $old = fn ($key, $default = null) => $bag->any() ? old($key, $default) : $default;
@endphp

<div class="modal fade md-modal" id="dmUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mdIcon }}"></i> Sửa Khai Báo Vật Tư Của Phòng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($mdRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ $old('id') }}">

                <div class="modal-body">

                    {{-- Vật tư là khoá của dòng nên chỉ đọc: khai nhầm thì khoá dòng cũ, khai dòng mới --}}
                    <div class="form-group">
                        <label>Vật Tư</label>
                        <input type="text" class="form-control md-readonly dm-material-view" readonly>
                        <small class="md-sub">Không đổi được vật tư của một dòng đã khai. Nếu khai nhầm, hãy
                            <b>khoá</b> dòng này rồi khai dòng mới để giữ lại vết.</small>
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
                            <small class="md-sub">Đổi đơn vị chỉ đổi cách khai từ nay về sau, số liệu các phiếu đã
                                lưu giữ nguyên.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ngưỡng Tồn Tối Thiểu</label>
                        <input type="number" name="min_stock" min="0" step="0.0001"
                            class="form-control {{ $bag->has('min_stock') ? 'is-invalid' : '' }}"
                            value="{{ $old('min_stock') }}" placeholder="Để trống nếu chưa đặt ngưỡng">
                        @if ($bag->has('min_stock'))
                            <span class="md-error">{{ $bag->first('min_stock') }}</span>
                        @endif
                        <small class="md-sub">Theo <b class="dm-unit-hint">đơn vị</b> đã chọn ở trên.</small>
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

        /* ---------- Ô chỉ đọc, đổ theo dòng đang sửa ---------- */
        $(document).on('click', '.btn-md-edit[data-modal="#dmUpdateModal"]', function() {
            var row = $(this).data('row') || {};
            var $form = $('#dmUpdateModal').find('form');

            var name = row.material_name || '';
            if (row.manufacturer_name) {
                name += ' (' + row.manufacturer_name + ')';
            }
            $form.find('.dm-material-view').val(name);

            // Select2 chỉ vẽ lại khi có sự kiện change, .val() thôi là chưa đủ
            $form.find('.cat-select').each(function() {
                var field = $(this).attr('name');
                $(this).val(row[field] === undefined || row[field] === null ? '' : row[field]).trigger('change');
            });

            var short = $form.find('.cat-select[name="unit_id"] option:selected').data('short');
            $form.find('.dm-unit-hint').text(short || 'đơn vị');
        });
    });
</script>

@if ($bag->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#dmUpdateModal').modal('show');
        });
    </script>
@endif
