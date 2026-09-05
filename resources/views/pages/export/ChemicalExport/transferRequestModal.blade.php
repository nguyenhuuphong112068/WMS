<div class="modal fade md-modal" id="transferRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-people-arrows mr-1"></i> Tạo Đề Nghị Chuyển Hoá Chất Liên Phòng Ban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.chemicalExport.transferRequestStore') }}" method="POST" autocomplete="off" id="formChemTransferRequest">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" name="action_type" id="chemTransferRequestActionType" value="send">

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
                        <div class="d-flex align-items-center">
                            <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                            </label>
                            <div style="min-width: 280px;">
                                <select name="to_department_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                    <option value="">-- Chọn phòng ban đang giữ hoá chất --</option>
                                    @foreach ($transferDepartments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->shortName }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-chem-transfer-row shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Thêm hoá chất
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        <style>
                            #tableChemTransferRows td { vertical-align: middle !important; padding: 8px !important; }
                            #tableChemTransferRows th {
                                white-space: nowrap; vertical-align: middle; background-color: #f1f5f9;
                                font-size: 0.88rem; padding: 10px 8px !important;
                            }
                            #tableChemTransferRows .form-control {
                                min-height: 38px !important; height: 38px !important; font-size: 0.9rem !important;
                                line-height: 1.5 !important; padding: 6px 10px !important;
                            }
                            #tableChemTransferRows textarea.auto-resize {
                                resize: vertical; min-height: 38px !important; height: 38px !important;
                                line-height: 1.4 !important; overflow-y: auto;
                            }
                        </style>
                        <table class="table table-bordered mb-0" id="tableChemTransferRows" style="font-size: 0.9rem;">
                            <thead class="bg-light">
                                <tr class="text-center">
                                    <th style="min-width: 260px">Hoá Chất <span class="text-danger">*</span></th>
                                    <th style="min-width: 130px">Số Lượng ĐN <span class="text-danger">*</span></th>
                                    <th style="min-width: 100px">ĐVT</th>
                                    <th style="min-width: 220px">Ghi Chú</th>
                                    <th style="min-width: 45px" class="text-center">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="chem-transfer-row">
                                    <td>
                                        <select name="items[0][category_id]" class="form-control select-chem-transfer-category" required>
                                            <option value="">-- Chọn hoá chất --</option>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: '' }}">
                                                    {{ $cat->chem_name }} ({{ $cat->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0.0001" name="items[0][requested_amount]" class="form-control text-right" placeholder="0.0000" required>
                                    </td>
                                    <td>
                                        <select name="items[0][requested_unit]" class="form-control select-chem-transfer-unit">
                                            <option value="">-- ĐVT --</option>
                                            @foreach ($units as $u)
                                                @php $uVal = $u->short_name ?: $u->name; @endphp
                                                <option value="{{ $uVal }}">{{ $uVal }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="items[0][note]" class="form-control auto-resize" rows="1" placeholder="Ghi chú..."></textarea>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-xs btn-danger btn-remove-chem-transfer-row" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-3 mb-0">
                        <label class="font-weight-bold" style="font-size: 0.9rem;">Ghi chú chung</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú cho cả phiếu đề nghị..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-outline-primary btn-submit-chem-transfer-action" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <button type="submit" class="btn btn-primary btn-submit-chem-transfer-action" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->transferCreateErrors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#transferRequestModal').modal('show');
    });
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var chemTransferRowIdx = 1;

        $(document).on('click', '.btn-submit-chem-transfer-action', function() {
            var action = $(this).data('action') || 'send';
            $('#chemTransferRequestActionType').val(action);
        });

        $(document).on('input', '#tableChemTransferRows .auto-resize', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        $('.btn-add-chem-transfer-row').click(function() {
            var $tbody = $('#tableChemTransferRows tbody');
            var $newRow = $tbody.find('tr:first').clone();

            $newRow.find('select, input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + chemTransferRowIdx + ']'));
                }
                $(this).val(null);
            });

            $newRow.find('.btn-remove-chem-transfer-row').prop('disabled', false);
            $tbody.append($newRow);
            chemTransferRowIdx++;
            updateChemTransferDeleteButtons();
        });

        $(document).on('click', '.btn-remove-chem-transfer-row', function() {
            if ($('#tableChemTransferRows tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateChemTransferDeleteButtons();
            }
        });

        function updateChemTransferDeleteButtons() {
            var rows = $('#tableChemTransferRows tbody tr');
            rows.find('.btn-remove-chem-transfer-row').prop('disabled', rows.length <= 1);
        }
    });
</script>
