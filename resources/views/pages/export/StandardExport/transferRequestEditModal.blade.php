@foreach ($transferSent->where('status', 'draft') as $req)
    @php $items = $transferItems[$req->id] ?? collect(); @endphp
    <div class="modal fade md-modal" id="transferRequestEditModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2 text-warning"></i> Điều Chỉnh Đề Nghị Liên Phòng Ban: {{ $req->code }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('pages.export.standardExport.transferRequestUpdate') }}" method="POST" autocomplete="off" class="form-edit-transfer-request">
                    @csrf
                    <input type="hidden" name="transfer_request_id" value="{{ $req->id }}">
                    <input type="hidden" name="action_type" class="edit-transfer-action-type" value="draft">

                    <div class="modal-body p-3">
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
                                                    <option value="{{ $dept->id }}" {{ $req->to_department_id == $dept->id ? 'selected' : '' }}>
                                                        {{ $dept->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-add-edit-transfer-row shadow-sm" data-modal="#transferRequestEditModal_{{ $req->id }}">
                                    <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-bordered mb-0 table-edit-transfer-rows" style="font-size: 0.9rem;">
                                <thead class="bg-light">
                                    <tr class="text-center">
                                        <th style="min-width: 260px">Chất Chuẩn <span class="text-danger">*</span></th>
                                        <th style="min-width: 130px">Số Lượng ĐN <span class="text-danger">*</span></th>
                                        <th style="min-width: 100px">ĐVT</th>
                                        <th style="min-width: 200px">Chỉ Tiêu Kiểm</th>
                                        <th style="min-width: 200px">Ghi Chú</th>
                                        <th style="min-width: 45px" class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $idx => $item)
                                        <tr class="transfer-row">
                                            <td>
                                                <select name="items[{{ $idx }}][category_id]" class="form-control select-transfer-category" required>
                                                    <option value="">-- Chọn chất chuẩn --</option>
                                                    @foreach ($standardCategories as $cat)
                                                        <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}" {{ $item->category_id == $cat->id ? 'selected' : '' }}>
                                                            {{ $cat->standard_name }} ({{ $cat->code }} v{{ $cat->version }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" min="0.0001" name="items[{{ $idx }}][requested_amount]" class="form-control text-right" value="{{ (float) $item->requested_amount }}" required>
                                            </td>
                                            <td>
                                                <select name="items[{{ $idx }}][requested_unit]" class="form-control">
                                                    <option value="">-- ĐVT --</option>
                                                    @foreach ($units as $u)
                                                        @php $uVal = $u->short_name ?: $u->name; @endphp
                                                        <option value="{{ $uVal }}" {{ $item->requested_unit === $uVal ? 'selected' : '' }}>{{ $uVal }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="items[{{ $idx }}][purpose_id]" class="form-control">
                                                    <option value="">-- Chỉ tiêu kiểm --</option>
                                                    @foreach ($purposes as $purpose)
                                                        <option value="{{ $purpose->id }}" {{ $item->purpose_id == $purpose->id ? 'selected' : '' }}>{{ $purpose->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <textarea name="items[{{ $idx }}][note]" class="form-control auto-resize" rows="1">{{ $item->note }}</textarea>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-xs btn-danger btn-remove-edit-transfer-row" {{ count($items) <= 1 ? 'disabled' : '' }}>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="transfer-row">
                                            <td>
                                                <select name="items[0][category_id]" class="form-control select-transfer-category" required>
                                                    <option value="">-- Chọn chất chuẩn --</option>
                                                    @foreach ($standardCategories as $cat)
                                                        <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}">
                                                            {{ $cat->standard_name }} ({{ $cat->code }} v{{ $cat->version }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.0001" min="0.0001" name="items[0][requested_amount]" class="form-control text-right" placeholder="0.0000" required>
                                            </td>
                                            <td>
                                                <select name="items[0][requested_unit]" class="form-control">
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
                                                <textarea name="items[0][note]" class="form-control auto-resize" rows="1"></textarea>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-xs btn-danger btn-remove-edit-transfer-row" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group mt-3 mb-0">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">Ghi chú chung</label>
                            <textarea name="note" class="form-control" rows="2">{{ $req->note }}</textarea>
                        </div>
                    </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-outline-primary btn-submit-edit-transfer-action" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                        <button type="submit" class="btn btn-primary btn-submit-edit-transfer-action" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-edit-transfer-action', function() {
            var action = $(this).data('action') || 'draft';
            $(this).closest('form').find('.edit-transfer-action-type').val(action);
        });

        $(document).on('click', '.btn-add-edit-transfer-row', function() {
            var modalId = $(this).data('modal');
            var $tbody = $(modalId).find('table.table-edit-transfer-rows tbody');
            var newIdx = $tbody.find('tr').length;
            var $newRow = $tbody.find('tr:first').clone();

            $newRow.find('select, input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + newIdx + ']'));
                }
                $(this).val(null);
            });

            $newRow.find('.btn-remove-edit-transfer-row').prop('disabled', false);
            $tbody.append($newRow);
            $tbody.find('.btn-remove-edit-transfer-row').prop('disabled', false);
        });

        $(document).on('click', '.btn-remove-edit-transfer-row', function() {
            var $tbody = $(this).closest('tbody');
            if ($tbody.find('tr').length > 1) {
                $(this).closest('tr').remove();
                if ($tbody.find('tr').length <= 1) {
                    $tbody.find('.btn-remove-edit-transfer-row').prop('disabled', true);
                }
            }
        });
    });
</script>
