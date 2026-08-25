<div class="modal fade md-modal" id="issueModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-hand-holding-medical text-success mr-1"></i> Cấp Phát Ống Chuẩn Cho Tổ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.standardExport.issueStore') }}" method="POST" autocomplete="off" id="formStdIssue">
                @csrf
                <input type="hidden" name="item_id" id="issue_item_id" value="">

                <div class="modal-body">
                    <div class="card mb-3 bg-light border">
                        <div class="card-body py-2 px-3" style="font-size: 0.88rem;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div>Mã đề nghị: <b id="issue_req_code" class="text-primary">—</b></div>
                                    <div>Tổ đề nghị: <b id="issue_group_name">—</b></div>
                                    <div>Chất chuẩn: <b id="issue_std_name" class="text-dark">—</b></div>
                                    <div>Qui cách: <b id="issue_spec">—</b></div>
                                </div>
                                <div class="col-md-6">
                                    <div>Số lượng đề nghị: <b id="issue_req_amount" class="text-success">—</b></div>
                                    <div>Tên sản phẩm: <b id="issue_product_name">—</b></div>
                                    <div>Chỉ tiêu: <b id="issue_criteria">—</b></div>
                                    <div>Kiểm nghiệm viên: <b id="issue_analyst_name">—</b></div>
                                </div>
                            </div>
                            <div id="issue_item_note_wrap" class="mt-1 text-muted font-italic" style="display:none;">
                                <i class="fas fa-comment mr-1"></i>Ghi chú của tổ: <span id="issue_item_note"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required font-weight-bold">Chọn Ống Chuẩn Trong Kho Để Cấp</label>
                        <select name="import_id" id="issue_import_id" class="form-control" required>
                            <option value="">-- Chọn ống chuẩn trong kho --</option>
                            @foreach ($availableImports as $imp)
                                <option value="{{ $imp->id }}"
                                    data-category="{{ $imp->category_id ?? 0 }}"
                                    data-remaining="{{ $imp->remaining }}"
                                    data-unit="{{ $imp->unit_short_name ?: '' }}"
                                    data-location="{{ $imp->location_code ?? ($imp->location_name ?? '') }}">
                                    {{ $imp->code }} — {{ $imp->standard_name }} (Lô {{ $imp->batch_no ?: '—' }}, HSD {{ $imp->expired_date ? \Carbon\Carbon::parse($imp->expired_date)->format('d/m/Y') : '—' }}, tồn {{ $expNum($imp->remaining) }} {{ $imp->unit_short_name ?: '' }}{{ !empty($imp->location_code) ? ', Định khu: ' . $imp->location_code : '' }})
                                </option>
                            @endforeach
                        </select>
                        @error('import_id', 'issueErrors')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="required font-weight-bold">Số Lượng Thực Cấp</label>
                            <input type="number" step="0.0001" min="0.0001" name="issued_amount" id="issue_amount" class="form-control" placeholder="0.0000" required>
                            @error('issued_amount', 'issueErrors')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Đơn Vị Cấp</label>
                            <input type="text" name="issued_unit" id="issue_unit_input" class="form-control" placeholder="mg/lọ/gói...">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú cấp phát</label>
                        <input type="text" name="note" id="issue_note_input" class="form-control" placeholder="Ghi chú khi giao ống chuẩn cho tổ...">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Xác nhận cấp phát</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-std-issue', function() {
            var data = $(this).data('item');
            if (!data) return;

            $('#issue_item_id').val(data.id);
            $('#issue_req_code').text(data.request_code || '—');
            $('#issue_group_name').text(data.group_name || '—');
            $('#issue_std_name').text(data.standard_name || '—');
            $('#issue_spec').text(data.specification || '—');
            $('#issue_req_amount').text(data.requested_amount + ' ' + (data.requested_unit || ''));
            $('#issue_product_name').text(data.product_name || '—');
            $('#issue_criteria').text(data.test_criteria || '—');
            $('#issue_analyst_name').text(data.analyst_name || '—');

            if (data.note) {
                $('#issue_item_note').text(data.note);
                $('#issue_item_note_wrap').show();
            } else {
                $('#issue_item_note_wrap').hide();
            }

            $('#issue_amount').val(data.requested_amount);
            $('#issue_unit_input').val(data.requested_unit || '');
            $('#issue_note_input').val(data.note || '');

            // Lọc danh sách ống chuẩn trong kho theo chất chuẩn này
            $('#issue_import_id option').each(function() {
                var catId = $(this).data('category');
                if (!catId || catId == data.category_id) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            $('#issue_import_id').val('');

            $('#issueModal').modal('show');
        });

        $('#issue_import_id').change(function() {
            var unit = $(this).find('option:selected').data('unit');
            if (unit && !$('#issue_unit_input').val()) {
                $('#issue_unit_input').val(unit);
            }
        });
    });
</script>
