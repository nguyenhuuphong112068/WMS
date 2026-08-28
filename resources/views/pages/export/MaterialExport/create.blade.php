@php $bag = $errors->getBag('createErrors'); $ubag = $errors->getBag('updateErrors'); @endphp

{{-- ============ LẬP PHIẾU SỬ DỤNG / LOẠI BỎ ============ --}}
<div class="modal fade md-modal" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }} mr-2"></i><span class="me-modal-title">Loại bỏ vật tư</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'store') }}" method="POST">
                @csrf
                <input type="hidden" name="request_item_id" value="{{ old('request_item_id') }}">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Loại phiếu <span class="text-danger">*</span></label>
                        <div class="exp-types">
                            <label class="exp-type"><input type="radio" name="type" value="export" {{ old('type') === 'export' ? 'checked' : '' }}> Sử dụng (từ đề nghị đã cấp phát)</label>
                            <label class="exp-type"><input type="radio" name="type" value="cancel" {{ old('type', 'cancel') === 'cancel' ? 'checked' : '' }}> Loại bỏ hỏng / hết hạn</label>
                        </div>
                        @if ($bag->has('type')) <div class="md-error text-danger small">{{ $bag->first('type') }}</div> @endif
                    </div>

                    {{-- Nguồn: đề nghị đã cấp phát (type=export) --}}
                    <div class="form-group me-src-export">
                        <label>Dòng đề nghị đã cấp phát <span class="text-danger">*</span></label>
                        <div class="d-flex" style="gap:8px;">
                            <select class="form-control me-issued-group">
                                <option value="">-- Chọn Tổ để tra --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary me-load-issued"><i class="fas fa-sync-alt"></i></button>
                        </div>
                        <select class="form-control mt-2 me-issued-pick">
                            <option value="">-- Chọn Tổ rồi bấm tra --</option>
                        </select>
                        <div class="md-hint mt-1 me-issued-info"></div>
                        @if ($bag->has('request_item_id')) <div class="md-error text-danger small">{{ $bag->first('request_item_id') }}</div> @endif
                    </div>

                    {{-- Nguồn: chọn mã lô để loại bỏ (type=cancel) --}}
                    <div class="form-group me-src-cancel">
                        <label>Mã lô cần loại bỏ <span class="text-danger">*</span></label>
                        <select name="import_id" class="form-control exp-select {{ $bag->has('import_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn mã lô trong kho --</option>
                            @foreach ($availableImports as $i)
                                <option value="{{ $i->id }}" data-remaining="{{ $i->remaining }}" data-unit="{{ $i->unit_short_name }}"
                                    {{ old('import_id') == $i->id ? 'selected' : '' }} {{ $i->remaining <= 0 && $i->expired ? '' : '' }}>
                                    {{ $i->code }} — {{ $i->material_name }} (còn {{ $expNum($i->remaining) }} {{ $i->unit_short_name }}){{ $i->expired ? ' · HẾT HẠN' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bag->has('import_id')) <div class="md-error text-danger small">{{ $bag->first('import_id') }}</div> @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Số lượng <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" name="amount"
                                    class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}" value="{{ old('amount') }}" required>
                                <div class="input-group-append"><span class="input-group-text me-unit">—</span></div>
                            </div>
                            @if ($bag->has('amount')) <div class="md-error text-danger small">{{ $bag->first('amount') }}</div> @endif
                            <small class="text-muted me-remaining"></small>
                        </div>
                        <div class="form-group col-md-7 me-export-only">
                            <label>Tên sản phẩm</label>
                            <input type="text" name="product_name" maxlength="255" class="form-control" value="{{ old('product_name') }}">
                        </div>
                    </div>

                    <div class="form-row me-export-only">
                        <div class="form-group col-md-6">
                            <label>Số phiếu kiểm nghiệm</label>
                            <input type="text" name="test_report_no" maxlength="100" class="form-control" value="{{ old('test_report_no') }}">
                        </div>
                    </div>

                    <div class="form-group me-cancel-only">
                        <label>Lý do loại bỏ <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}">{{ old('reason') }}</textarea>
                        @if ($bag->has('reason')) <div class="md-error text-danger small">{{ $bag->first('reason') }}</div> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Ghi nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ ĐIỀU CHỈNH PHIẾU ============ --}}
<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Điều chỉnh phiếu sử dụng vật tư</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mã lô</label>
                        <input type="text" name="code" class="form-control exp-readonly" readonly>
                    </div>
                    <div class="form-group">
                        <label>Số lượng <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0.0001" name="amount"
                            class="form-control {{ $ubag->has('amount') ? 'is-invalid' : '' }}" required>
                        @if ($ubag->has('amount')) <div class="md-error text-danger small">{{ $ubag->first('amount') }}</div> @endif
                    </div>
                    <div class="form-group me-up-export">
                        <label>Tên sản phẩm</label>
                        <input type="text" name="product_name" maxlength="255" class="form-control">
                    </div>
                    <div class="form-group me-up-export">
                        <label>Số phiếu kiểm nghiệm</label>
                        <input type="text" name="test_report_no" maxlength="100" class="form-control">
                    </div>
                    <div class="form-group me-up-cancel">
                        <label>Lý do loại bỏ</label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Lý do điều chỉnh</label>
                        <textarea name="adjust_reason" rows="2" maxlength="500" class="form-control">{{ old('adjust_reason') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Ghi nhận điều chỉnh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var importMap = @json($expImportMap);
        var issuedUrl = @json(route($expRoute . 'getIssuedItems'));

        function applyType() {
            var t = $('#createModal [name="type"]:checked').val() || 'cancel';
            $('#createModal .me-modal-title').text(t === 'export' ? 'Lập phiếu sử dụng vật tư' : 'Loại bỏ vật tư hỏng / hết hạn');
            $('#createModal .me-src-export').toggle(t === 'export');
            $('#createModal .me-src-cancel').toggle(t === 'cancel');
            $('#createModal .me-export-only').toggle(t === 'export');
            $('#createModal .me-cancel-only').toggle(t === 'cancel');
        }
        $(document).on('change', '#createModal [name="type"]', applyType);

        // type=cancel: chọn mã lô -> đơn vị + tồn
        $(document).on('change', '#createModal [name="import_id"]', function () {
            var $o = $(this).find(':selected');
            $('#createModal .me-unit').text($o.data('unit') || '—');
            $('#createModal .me-remaining').text($o.val() ? ('Tồn còn: ' + ($o.data('remaining') || 0)) : '');
        });

        // type=export: tra dòng đề nghị đã cấp phát
        $(document).on('click', '#createModal .me-load-issued', function () {
            var gid = $('#createModal .me-issued-group').val();
            var $pick = $('#createModal .me-issued-pick').html('<option value="">Đang tải...</option>');
            if (!gid) { $pick.html('<option value="">-- Chọn Tổ rồi bấm tra --</option>'); return; }
            $.getJSON(issuedUrl, { group_id: gid }).done(function (res) {
                var rows = res.rows || [];
                if (!rows.length) { $pick.html('<option value="">Tổ này chưa có dòng nào đã cấp phát mà chưa dùng</option>'); return; }
                $pick.empty().append('<option value="">-- Chọn dòng đã cấp phát --</option>');
                rows.forEach(function (r) {
                    $pick.append($('<option></option>')
                        .val(r.id)
                        .attr('data-import', r.import_id)
                        .attr('data-code', r.import_code)
                        .attr('data-amount', r.issued_amount || r.requested_amount)
                        .attr('data-unit', r.issued_unit || '')
                        .attr('data-remaining', r.actual_remaining)
                        .attr('data-product', r.product_name || '')
                        .text(r.import_code + ' — ' + r.display_name + ' (cấp ' + (r.issued_amount || r.requested_amount) + ' ' + (r.issued_unit || '') + ', tồn ' + r.actual_remaining + ')'));
                });
            });
        });
        $(document).on('change', '#createModal .me-issued-pick', function () {
            var $o = $(this).find(':selected');
            $('#createModal [name="request_item_id"]').val($o.val() || '');
            $('#createModal [name="amount"]').val($o.data('amount') || '');
            $('#createModal [name="product_name"]').val($o.data('product') || '');
            $('#createModal .me-unit').text($o.data('unit') || '—');
            $('#createModal .me-remaining').text($o.val() ? ('Mã lô ' + $o.data('code') + ', tồn còn ' + $o.data('remaining')) : '');
        });

        // mở modal sử dụng từ requestDetail (đã cấp phát)
        $(document).on('click', '.btn-me-use', function () {
            var d = $(this).data() || {};
            var $f = $('#createModal form');
            $f[0].reset();
            $f.find('[name="type"][value="export"]').prop('checked', true);
            applyType();
            $f.find('[name="request_item_id"]').val(d.itemId);
            $f.find('[name="amount"]').val(d.amount || '');
            $f.find('.me-unit').text(d.unit || '—');
            $f.find('.me-remaining').text('Mã lô ' + (d.code || '') + ', tồn còn ' + (d.remaining || 0));
            $f.find('.me-issued-pick').html('<option value="' + d.itemId + '" selected>' + (d.code || '') + '</option>');
            $('#createModal').modal('show');
        });

        function applyUpdateType() {
            // Loại phiếu không đổi khi sửa; suy từ có reason hay không sau khi đổ dữ liệu
        }
        $(document).on('click', '.btn-me-edit', function () {
            var r = $(this).data('row') || {};
            var isCancel = r.type === 'cancel';
            $('#updateModal .me-up-export').toggle(!isCancel);
            $('#updateModal .me-up-cancel').toggle(isCancel);
        });

        applyType();
        @if ($bag->any()) $(function () { $('#createModal').modal('show'); applyType(); }); @endif
        @if ($ubag->any()) $(function () { $('#updateModal').modal('show'); }); @endif
    })();
</script>
