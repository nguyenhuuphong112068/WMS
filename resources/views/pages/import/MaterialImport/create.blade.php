@php $bag = $errors->getBag('createErrors'); @endphp

<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 860px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('pages.import.materialImport.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="{{ $impIcon }} mr-2"></i>Nhập vật tư</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Vật tư <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control imp-select mi-category {{ $bag->has('category_id') ? 'is-invalid' : '' }}"
                                data-defaults='@json($categoryDefaults)' required>
                                <option value="">-- Chọn vật tư phòng đang dùng --</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->material_name }} — {{ $c->manufacturer_short_name ?: $c->manufacturer_name }}
                                        @if ($c->technical_specification) ({{ $c->technical_specification }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('category_id')) <div class="md-error text-danger small">{{ $bag->first('category_id') }}</div> @endif
                            <div class="md-hint mi-info mt-1"></div>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mã lô</label>
                            <input type="text" class="form-control imp-readonly" value="{{ $codePreview }}" readonly>
                            <small class="text-muted">Sinh tự động khi lưu: VT + mã phòng ban + chuỗi ngẫu nhiên.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số lượng / lô <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" name="amount"
                                    class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}" value="{{ old('amount') }}" required>
                                <div class="input-group-append"><span class="input-group-text mi-unit">—</span></div>
                            </div>
                            @if ($bag->has('amount')) <div class="md-error text-danger small">{{ $bag->first('amount') }}</div> @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Số lô cần nhập</label>
                            <input type="number" min="1" max="50" name="quantity" class="form-control" value="{{ old('quantity', 1) }}">
                            <small class="text-muted">Nhập nhiều lô cùng thông tin, mỗi lô một mã.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày nhập</label>
                            <input type="date" name="imported_date" class="form-control" value="{{ old('imported_date', now()->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Hạn sử dụng</label>
                            <input type="date" name="expired_date" class="form-control {{ $bag->has('expired_date') ? 'is-invalid' : '' }}" value="{{ old('expired_date') }}">
                            <small class="text-muted">Có thể để trống nếu vật tư không có hạn.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Vị trí lưu trữ</label>
                            <select name="location_id" class="form-control imp-select {{ $bag->has('location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chưa xếp vị trí --</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }} ({{ $loc->code }}) — {{ $loc->warehouse_name }} / {{ $loc->room_name }} / {{ $loc->shelf_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tài liệu đính kèm</label>
                        <input type="file" name="attachments[]" class="form-control-file" multiple>
                        <small class="text-muted">Tối đa 10MB / file.</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" rows="2" maxlength="500" class="form-control">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu phiếu nhập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        function syncCat($sel) {
            var defaults = $sel.data('defaults') || {};
            var d = defaults[$sel.val()] || {};
            var $modal = $sel.closest('.modal');
            $modal.find('.mi-unit').text(d.unit_short_name || '—');
            $modal.find('.mi-info').html(d.info_html || '');
        }
        $(document).on('change', '#createModal .mi-category', function () { syncCat($(this)); });
        $(document).on('click', '.btn-md-create', function () {
            setTimeout(function () { syncCat($('#createModal .mi-category')); }, 60);
        });
        @if ($bag->any())
            $(function () { $('#createModal').modal('show'); syncCat($('#createModal .mi-category')); });
        @endif
    })();
</script>
