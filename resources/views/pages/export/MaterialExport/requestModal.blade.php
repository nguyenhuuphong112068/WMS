@php $bag = $errors->getBag('requestCreateErrors'); @endphp

<div class="modal fade md-modal" id="reqCreateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 92vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-signature mr-2"></i>Tạo Đề Nghị Cấp Phát Vật Tư</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($expRoute . 'requestStore') }}" method="POST" id="reqCreateForm">
                @csrf
                <input type="hidden" name="action_type" id="reqCreateAction" value="send">
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tổ đề nghị <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-control {{ $bag->has('group_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn Tổ --</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                            @if ($bag->has('group_id')) <div class="md-error text-danger small">{{ $bag->first('group_id') }}</div> @endif
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <label class="mb-2">
                                <input type="checkbox" name="needs_director" value="1" {{ old('needs_director') ? 'checked' : '' }}>
                                Cần Ban Giám Đốc phê duyệt
                            </label>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ghi chú chung</label>
                            <input type="text" name="note" maxlength="500" class="form-control" value="{{ old('note') }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <b>Danh sách vật tư đề nghị</b>
                        <button type="button" class="btn btn-sm btn-outline-primary me-add-row"><i class="fas fa-plus mr-1"></i>Thêm dòng</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th style="width:200px">Vật tư (danh mục)</th>
                                    <th style="width:160px">Hoặc tên tự nhập</th>
                                    <th style="width:150px">Quy cách</th>
                                    <th style="width:100px">SL đề nghị</th>
                                    <th style="width:110px">Đơn vị</th>
                                    <th style="width:150px">Sản phẩm</th>
                                    <th>Mục đích</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody class="me-rows"></tbody>
                        </table>
                    </div>
                    @foreach ($bag->keys() as $k)
                        @if (str_starts_with($k, 'items')) <div class="md-error text-danger small">{{ $bag->first($k) }}</div> @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-outline-primary me-btn-draft"><i class="fas fa-save mr-1"></i>Lưu tạm</button>
                    <button type="submit" class="btn btn-primary me-btn-send"><i class="fas fa-paper-plane mr-1"></i>Trình ký</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="meRowTemplate">
    <tr>
        <td>
            <select name="items[__i__][category_id]" class="form-control form-control-sm me-cat">
                <option value="">-- Ngoài danh mục --</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" data-unit="{{ $c->unit_short_name }}" data-spec="{{ $c->technical_specification }}">
                        {{ $c->material_name }} — {{ $c->manufacturer_short_name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="items[__i__][material_name]" maxlength="255" class="form-control form-control-sm"></td>
        <td><input type="text" name="items[__i__][technical_specification]" maxlength="255" class="form-control form-control-sm me-spec"></td>
        <td><input type="number" step="0.0001" min="0.0001" name="items[__i__][requested_amount]" class="form-control form-control-sm" required></td>
        <td>
            <select name="items[__i__][requested_unit]" class="form-control form-control-sm me-unit">
                <option value="">--</option>
                @foreach ($units as $u)
                    <option value="{{ $u->short_name }}">{{ $u->short_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="items[__i__][product_name]" maxlength="255" class="form-control form-control-sm"></td>
        <td><input type="text" name="items[__i__][purpose]" maxlength="500" class="form-control form-control-sm"></td>
        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger me-del-row">&times;</button></td>
    </tr>
</template>

<script>
    (function () {
        var idx = 0;
        function addRow(tbodySel) {
            var html = document.getElementById('meRowTemplate').innerHTML.replace(/__i__/g, idx++);
            $(tbodySel).append(html);
        }
        $(document).on('click', '.me-add-row', function () { addRow('#reqCreateModal .me-rows'); });
        $(document).on('click', '.me-del-row', function () { $(this).closest('tr').remove(); });
        $(document).on('change', '.me-cat', function () {
            var $o = $(this).find(':selected'), $tr = $(this).closest('tr');
            if ($o.val()) {
                $tr.find('.me-unit').val($o.data('unit') || '');
                if (!$tr.find('.me-spec').val()) $tr.find('.me-spec').val($o.data('spec') || '');
                $tr.find('[name$="[material_name]"]').val('').prop('disabled', true);
            } else {
                $tr.find('[name$="[material_name]"]').prop('disabled', false);
            }
        });
        $(document).on('click', '.me-btn-draft', function () { $('#reqCreateAction').val('draft'); });
        $(document).on('click', '.me-btn-send', function () { $('#reqCreateAction').val('send'); });
        $('#reqCreateModal').on('shown.bs.modal', function () {
            if (!$('#reqCreateModal .me-rows tr').length) addRow('#reqCreateModal .me-rows');
        });
        @if ($bag->any())
            $(function () {
                $('#reqCreateModal').modal('show');
                var old = @json(old('items', []));
                (old.length ? old : [{}]).forEach(function (row) {
                    addRow('#reqCreateModal .me-rows');
                    var $tr = $('#reqCreateModal .me-rows tr').last();
                    Object.keys(row).forEach(function (k) { $tr.find('[name$="[' + k + ']"]').val(row[k]); });
                });
            });
        @endif
    })();
</script>
