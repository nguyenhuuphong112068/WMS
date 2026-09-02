@php $bag = $errors->getBag('updateErrors'); @endphp

<div class="modal fade md-modal" id="updateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 860px;">
        <div class="modal-content">
            <form method="POST" action="{{ route('pages.import.materialImport.update') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Điều chỉnh phiếu nhập vật tư</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Vật tư <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control imp-select mi-up-category {{ $bag->has('category_id') ? 'is-invalid' : '' }}" required>
                                <option value="">-- Chọn vật tư phòng đang dùng --</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">
                                        {{ $c->material_name }} — {{ $c->manufacturer_short_name ?: $c->manufacturer_name }}
                                        @if ($c->technical_specification) ({{ $c->technical_specification }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @if ($bag->has('category_id')) <div class="md-error text-danger small">{{ $bag->first('category_id') }}</div> @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Mã xuất nhập</label>
                            <input type="text" name="code" class="form-control imp-readonly" readonly>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Số lượng <span class="text-danger">*</span></label>
                            <input type="number" step="0.0001" min="0.0001" name="amount"
                                class="form-control {{ $bag->has('amount') ? 'is-invalid' : '' }}" required>
                            @if ($bag->has('amount')) <div class="md-error text-danger small">{{ $bag->first('amount') }}</div> @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày nhập</label>
                            <input type="text" class="form-control imp-readonly mi-up-imported-date" readonly>
                            <small class="text-muted">Ngày ghi nhận lúc lập phiếu, không sửa được.</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Hạn sử dụng</label>
                            <input type="date" name="expired_date" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vị trí lưu trữ</label>
                        <select name="location_id" class="form-control imp-select">
                            <option value="">-- Chưa xếp vị trí --</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">
                                    {{ $loc->code }} — {{ $loc->warehouse_name }} / {{ $loc->room_name }} / {{ $loc->shelf_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tài liệu đính kèm</label>
                        <div class="mi-existing-files mb-2 small"></div>
                        <input type="file" name="attachments[]" class="form-control-file" multiple>
                        <small class="text-muted">Thêm file mới (tối đa 10MB / file).</small>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Lý do điều chỉnh <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500"
                            class="form-control {{ $bag->has('reason') ? 'is-invalid' : '' }}">{{ old('reason') }}</textarea>
                        @if ($bag->has('reason')) <div class="md-error text-danger small">{{ $bag->first('reason') }}</div> @endif
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
    document.addEventListener('DOMContentLoaded', function () {
        var delUrl = @json(route('pages.import.materialImport.deleteAttachment'));
        var csrf = @json(csrf_token());

        function renderFiles(list) {
            var $box = $('#updateModal .mi-existing-files').empty();
            (list || []).forEach(function (f) {
                var $row = $('<div class="d-flex align-items-center mb-1"></div>');
                $row.append($('<span class="mr-2"><i class="fas fa-paperclip text-primary mr-1"></i></span>').append(document.createTextNode(f.file_name)));
                var $btn = $('<button type="button" class="btn btn-xs btn-outline-danger">&times;</button>');
                $btn.on('click', function () {
                    if (!confirm('Xoá file "' + f.file_name + '"?')) return;
                    $.post(delUrl, { _token: csrf, id: f.id }).done(function () { $row.remove(); });
                });
                $row.append($btn);
                $box.append($row);
            });
        }

        $(document).on('click', '.btn-md-edit', function () {
            var row = $(this).data('row') || {};
            var $form = $('#updateModal form');
            ['id', 'code', 'amount', 'expired_date', 'note'].forEach(function (k) {
                $form.find('[name="' + k + '"]').val(row[k] == null ? '' : row[k]);
            });
            // Ngày nhập chỉ để xem: hiển thị d/m/Y, không gửi lên server
            var imp = (row.imported_date || '').substring(0, 10).split('-');
            $form.find('.mi-up-imported-date').val(imp.length === 3 ? imp[2] + '/' + imp[1] + '/' + imp[0] : '—');
            $form.find('[name="category_id"]').val(row.category_id || '').trigger('change');
            $form.find('[name="location_id"]').val(row.location_id || '').trigger('change');
            $form.find('[name="reason"]').val('');
            renderFiles(row.attachments);
        });

        @if ($bag->any())
            $(function () { $('#updateModal').modal('show'); });
        @endif
    });
</script>
