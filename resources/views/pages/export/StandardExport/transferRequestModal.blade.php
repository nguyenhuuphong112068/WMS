<div class="modal fade md-modal" id="transferRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-people-arrows mr-1"></i> Tạo Đề Nghị Cấp Phát Chuẩn Liên Phòng Ban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.standardExport.transferRequestStore') }}" method="POST" autocomplete="off" id="formStdTransferRequest">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" name="action_type" id="transferRequestActionType" value="send">

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
                        <div class="d-flex align-items-center">
                            <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                            </label>
                            <div style="min-width: 280px;">
                                <select name="to_department_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                    <option value="">-- Chọn phòng ban đang giữ chuẩn --</option>
                                    @foreach ($transferDepartments->groupBy('company_name') as $companyName => $depts)
                                        <optgroup label="{{ $companyName ?: 'Chưa gán công ty' }}">
                                            @foreach ($depts as $dept)
                                                <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-std-stock-picker"
                                data-target-rows="#tableTransferRows tbody">
                                <i class="fas fa-vial mr-1"></i> Danh mục chất chuẩn phòng nguồn
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-transfer-row shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        <style>
                            #tableTransferRows td { vertical-align: middle !important; padding: 8px !important; }
                            #tableTransferRows th {
                                white-space: nowrap; vertical-align: middle; background-color: #f1f5f9;
                                font-size: 0.88rem; padding: 10px 8px !important;
                            }
                            #tableTransferRows .form-control {
                                min-height: 38px !important; height: 38px !important; font-size: 0.9rem !important;
                                line-height: 1.5 !important; padding: 6px 10px !important;
                            }
                            #tableTransferRows textarea.auto-resize {
                                resize: vertical; min-height: 38px !important; height: 38px !important;
                                line-height: 1.4 !important; overflow-y: auto;
                            }
                        </style>
                        <table class="table table-bordered mb-0" id="tableTransferRows" style="font-size: 0.9rem;">
                            <thead class="bg-light">
                                <tr class="text-center">
                                    <th style="width: 130px">Mã Chất Chuẩn</th>
                                    <th style="min-width: 260px">Chất Chuẩn <span class="text-danger">*</span></th>
                                    <th style="min-width: 130px">Số Lượng ĐN <span class="text-danger">*</span></th>
                                    <th style="min-width: 100px">ĐVT</th>
                                    <th style="min-width: 200px">Chỉ Tiêu Kiểm</th>
                                    <th style="min-width: 200px">Ghi Chú</th>
                                    <th style="min-width: 45px" class="text-center">#</th>
                                </tr>
                            </thead>
                            <tbody data-next-idx="1">
                                <tr class="transfer-row">
                                    <td class="text-center">
                                        <span class="badge badge-secondary px-2 py-1 std-transfer-code" style="font-size: 0.82rem;"></span>
                                    </td>
                                    <td>
                                        <select name="items[0][category_id]" class="form-control select-transfer-category" required>
                                            <option value="">-- Chọn chất chuẩn --</option>
                                            @foreach ($standardCategories as $cat)
                                                <option value="{{ $cat->id }}" data-code="{{ $cat->code }} v{{ $cat->version }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}">
                                                    {{ $cat->code }} v{{ $cat->version }} - {{ $cat->standard_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" min="0.0001" name="items[0][requested_amount]" class="form-control text-right js-decimal" placeholder="0.0000" required>
                                    </td>
                                    <td>
                                        <select name="items[0][requested_unit]" class="form-control select-transfer-unit">
                                            <option value="">-- ĐVT --</option>
                                            @foreach ($units as $u)
                                                @php $uVal = $u->short_name ?: $u->name; @endphp
                                                <option value="{{ $uVal }}">{{ $uVal }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[0][purpose_id]" class="form-control">
                                            <option value="">-- Chỉ tiêu kiểm --</option>
                                            @foreach ($purposes as $purpose)
                                                <option value="{{ $purpose->id }}">{{ $purpose->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="items[0][note]" class="form-control auto-resize" rows="1" placeholder="Ghi chú..."></textarea>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-xs btn-danger btn-remove-transfer-row" disabled>
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
                    <button type="submit" class="btn btn-outline-primary btn-submit-transfer-action" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <button type="submit" class="btn btn-primary btn-submit-transfer-action" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
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
    /**
     * Thêm một dòng chất chuẩn vào bảng đề nghị liên phòng ban và trả về dòng vừa thêm.
     * Chỉ số items[i] đếm riêng từng bảng ở data-next-idx nên form tạo và các form điều
     * chỉnh không đụng nhau. Picker "Danh mục chất chuẩn phòng nguồn" cũng gọi hàm này.
     */
    window.stdAddTransferRow = function (tbody) {
        var $tbody = $(tbody);
        var next = parseInt($tbody.attr('data-next-idx') || $tbody.children('tr').length, 10);
        var $newRow = $tbody.children('tr').first().clone();

        $newRow.find('select, input, textarea').each(function () {
            var name = $(this).attr('name');

            if (name) {
                $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + next + ']'));
            }

            $(this).val(null);
        });

        $newRow.find('.std-transfer-code').text('');
        $tbody.attr('data-next-idx', next + 1);
        $tbody.append($newRow);
        $tbody.find('.btn-remove-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);

        return $tbody.children('tr').last();
    };

    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-transfer-action', function() {
            var action = $(this).data('action') || 'send';
            $('#transferRequestActionType').val(action);
        });

        $(document).on('input', '#tableTransferRows .auto-resize', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Chọn chất chuẩn thì hiện mã và điền sẵn đơn vị phòng mình đang dùng cho chuẩn đó
        $(document).on('change', '.select-transfer-category', function() {
            var $option = $(this).find('option:selected');
            var $row = $(this).closest('tr');
            var unit = $option.data('unit') || '';

            $row.find('.std-transfer-code').text($option.data('code') || '');

            if (unit && $row.find('select[name*="[requested_unit]"] option[value="' + unit + '"]').length) {
                $row.find('select[name*="[requested_unit]"]').val(unit);
            }
        });

        $(document).on('click', '.btn-add-transfer-row', function() {
            window.stdAddTransferRow('#tableTransferRows tbody');
        });

        $(document).on('click', '.btn-remove-transfer-row', function() {
            var $tbody = $(this).closest('tbody');

            if ($tbody.children('tr').length > 1) {
                $(this).closest('tr').remove();
                $tbody.find('.btn-remove-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);
            }
        });
    });
</script>
