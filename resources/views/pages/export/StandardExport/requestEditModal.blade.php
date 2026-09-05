@foreach ($requests->where('status', 'draft') as $req)
    @php
        $items = $requestItems[$req->id] ?? collect();
    @endphp
    <div class="modal fade md-modal" id="requestEditModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 95vw;" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-edit mr-2 text-warning"></i> Điều Chỉnh Đề Nghị Cấp Phát Chuẩn: {{ $req->code }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="{{ route('pages.export.standardExport.requestUpdate') }}" method="POST" autocomplete="off" class="form-edit-std-request" id="formEditReq_{{ $req->id }}">
                    @csrf
                    <input type="hidden" name="request_list_id" value="{{ $req->id }}">
                    <input type="hidden" name="action_type" class="edit-action-type" value="draft">

                    <div class="modal-body p-3">
                        {{-- Row 1: Tổ Đề Nghị + Buttons --}}
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
                            <div class="d-flex align-items-center">
                                <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                    <i class="fas fa-users mr-1 text-primary"></i> Tổ Đề Nghị <span class="text-danger">*</span>:
                                </label>
                                <div style="min-width: 260px;">
                                    <select name="group_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                        <option value="">-- Chọn tổ đề nghị --</option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->id }}" {{ $req->group_id == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-info mr-2 btn-open-inventory-picker shadow-sm" data-target-modal="#requestEditModal_{{ $req->id }}">
                                    <i class="fas fa-boxes-stacked mr-1"></i> Danh mục chuẩn tồn của phòng
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-add-edit-req-row shadow-sm" data-modal="#requestEditModal_{{ $req->id }}">
                                    <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <style>
                                .table-edit-req-rows td {
                                    vertical-align: middle !important;
                                    padding: 8px !important;
                                }
                                .table-edit-req-rows th {
                                    white-space: nowrap;
                                    vertical-align: middle;
                                    background-color: #f1f5f9;
                                    font-size: 0.88rem;
                                    padding: 10px 8px !important;
                                }
                                .table-edit-req-rows .form-control {
                                    min-height: 38px !important;
                                    height: 38px !important;
                                    font-size: 0.9rem !important;
                                    line-height: 1.5 !important;
                                    padding: 6px 10px !important;
                                }
                                .table-edit-req-rows textarea.auto-resize {
                                    resize: vertical;
                                    min-height: 38px !important;
                                    height: 38px !important;
                                    line-height: 1.4 !important;
                                    overflow-y: auto;
                                }
                                .table-edit-req-rows .select2-container--bootstrap4 .select2-selection--multiple {
                                    min-height: 38px !important;
                                    height: auto !important;
                                    padding: 3px 6px !important;
                                    font-size: 0.9rem !important;
                                }
                                .table-edit-req-rows .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
                                    margin-top: 3px !important;
                                    margin-bottom: 3px !important;
                                    font-size: 0.84rem !important;
                                    line-height: 1.4 !important;
                                }
                                .table-edit-req-rows .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
                                    height: 28px !important;
                                    margin-top: 3px !important;
                                }
                            </style>
                            <table class="table table-bordered mb-0 table-edit-req-rows" style="font-size: 0.9rem;">
                                <thead class="bg-light">
                                    <tr class="text-center">
                                        <th style="min-width: 130px">Mã Ống Chuẩn</th>
                                        <th style="min-width: 250px">Chất Chuẩn <span class="text-danger">*</span></th>
                                        <th style="min-width: 110px">Qui Cách</th>
                                        <th style="min-width: 160px">Nhà Sản Xuất</th>
                                        <th style="min-width: 150px">Mục Đích</th>
                                        <th style="min-width: 120px">Số Lượng ĐN <span class="text-danger">*</span></th>
                                        <th style="min-width: 95px">ĐVT</th>
                                        <th style="min-width: 160px">Tên Sản Phẩm</th>
                                        <th style="min-width: 220px">Chỉ Tiêu</th>
                                        <th style="min-width: 160px">Kiểm Nghiệm Viên</th>
                                        <th style="min-width: 160px">Ghi Chú</th>
                                        <th style="min-width: 45px" class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody>
                                            @forelse ($items as $idx => $item)
                                                @php
                                                    $selectedCriteria = array_map('trim', explode(',', $item->test_criteria ?? ''));
                                                @endphp
                                                <tr class="req-row">
                                                    <td class="text-center align-middle" style="background-color: #f8fafc;">
                                                        <span class="text-muted small font-italic">(Cấp sau)</span>
                                                    </td>
                                                    <td>
                                                        <select name="items[{{ $idx }}][category_id]" class="form-control select-req-category" required>
                                                            <option value="">-- Chọn chất chuẩn --</option>
                                                            @foreach ($standardCategories as $cat)
                                                                <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}" {{ $item->category_id == $cat->id ? 'selected' : '' }}>
                                                                    {{ $cat->standard_name }} ({{ $cat->code }} v{{ $cat->version }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="align-middle text-center" style="background-color: #f8fafc;">
                                                        <span class="cell-specification text-dark font-weight-500">{{ $item->specification ?: '—' }}</span>
                                                        <input type="hidden" name="items[{{ $idx }}][specification]" class="input-req-specification" value="{{ $item->specification }}">
                                                    </td>
                                                    <td class="align-middle" style="background-color: #f8fafc;">
                                                        <span class="cell-supplier text-dark font-weight-500">{{ $item->supplier_name ?: '—' }}</span>
                                                        <input type="hidden" name="items[{{ $idx }}][supplier_id]" class="input-req-supplier" value="{{ $item->supplier_id }}">
                                                    </td>
                                                    <td class="align-middle" style="background-color: #f8fafc;">
                                                        <span class="cell-purpose text-dark font-weight-500">{{ $item->purpose_name ?: '—' }}</span>
                                                        <input type="hidden" name="items[{{ $idx }}][purpose_id]" class="input-req-purpose" value="{{ $item->purpose_id }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.0001" min="0.0001" name="items[{{ $idx }}][requested_amount]" class="form-control text-right" value="{{ (float)$item->requested_amount }}" required>
                                                    </td>
                                                    <td>
                                                        <select name="items[{{ $idx }}][requested_unit]" class="form-control select-req-unit">
                                                            <option value="">-- ĐVT --</option>
                                                            @foreach ($units as $u)
                                                                @php $uVal = $u->short_name ?: $u->name; @endphp
                                                                <option value="{{ $uVal }}">{{ $uVal }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" list="productList" name="items[{{ $idx }}][product_name]" class="form-control" value="{{ $item->product_name }}" placeholder="Tên SP...">
                                                    </td>
                                                    <td style="min-width: 220px;">
                                                        <select name="items[{{ $idx }}][test_criteria][]" class="form-control select2-criteria-edit" multiple="multiple">
                                                            @foreach ($purposes as $purpose)
                                                                <option value="{{ $purpose->name }}" {{ in_array($purpose->name, $selectedCriteria) ? 'selected' : '' }}>{{ $purpose->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="items[{{ $idx }}][analyst_id]" class="form-control">
                                                            <option value="">-- KNV --</option>
                                                            @foreach ($analysts as $an)
                                                                <option value="{{ $an->id }}" {{ $item->analyst_id == $an->id ? 'selected' : '' }}>{{ $an->fullName }} ({{ $an->userName }})</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea name="items[{{ $idx }}][note]" class="form-control auto-resize" rows="1" placeholder="Ghi chú...">{{ $item->note }}</textarea>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-xs btn-danger btn-remove-edit-req-row" {{ count($items) <= 1 ? 'disabled' : '' }}>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                {{-- Default empty row --}}
                                                <tr class="req-row">
                                                    <td class="text-center align-middle" style="background-color: #f8fafc;">
                                                        <span class="text-muted small font-italic">(Cấp sau)</span>
                                                    </td>
                                                    <td>
                                                        <select name="items[0][category_id]" class="form-control form-control-sm select-req-category" required>
                                                            <option value="">-- Chọn chất chuẩn --</option>
                                                            @foreach ($standardCategories as $cat)
                                                                <option value="{{ $cat->id }}" data-unit="{{ $cat->unit_short_name ?: $cat->unit_name }}">
                                                                    {{ $cat->standard_name }} ({{ $cat->code }} v{{ $cat->version }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="align-middle text-center" style="background-color: #f8fafc;">
                                                        <span class="cell-specification text-dark font-weight-500">—</span>
                                                        <input type="hidden" name="items[0][specification]" class="input-req-specification">
                                                    </td>
                                                    <td class="align-middle" style="background-color: #f8fafc;">
                                                        <span class="cell-supplier text-dark font-weight-500">—</span>
                                                        <input type="hidden" name="items[0][supplier_id]" class="input-req-supplier">
                                                    </td>
                                                    <td class="align-middle" style="background-color: #f8fafc;">
                                                        <span class="cell-purpose text-dark font-weight-500">—</span>
                                                        <input type="hidden" name="items[0][purpose_id]" class="input-req-purpose">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.0001" min="0.0001" name="items[0][requested_amount]" class="form-control form-control-sm text-right" placeholder="0.0000" required>
                                                    </td>
                                                    <td>
                                                        <select name="items[0][requested_unit]" class="form-control form-control-sm select-req-unit">
                                                            <option value="">-- ĐVT --</option>
                                                            @foreach ($units as $u)
                                                                @php $uVal = $u->short_name ?: $u->name; @endphp
                                                                <option value="{{ $uVal }}">{{ $uVal }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" list="productList" name="items[0][product_name]" class="form-control form-control-sm" placeholder="Tên SP...">
                                                    </td>
                                                    <td style="min-width: 200px;">
                                                        <select name="items[0][test_criteria][]" class="form-control form-control-sm select2-criteria-edit" multiple="multiple">
                                                            @foreach ($purposes as $purpose)
                                                                <option value="{{ $purpose->name }}">{{ $purpose->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="items[0][analyst_id]" class="form-control form-control-sm">
                                                            <option value="">-- KNV --</option>
                                                            @foreach ($analysts as $an)
                                                                <option value="{{ $an->id }}">{{ $an->fullName }} ({{ $an->userName }})</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea name="items[0][note]" class="form-control form-control-sm auto-resize" rows="1" placeholder="Ghi chú..."></textarea>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <button type="button" class="btn btn-xs btn-danger btn-remove-edit-req-row" disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-outline-primary btn-submit-edit-action" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                        <button type="submit" class="btn btn-primary btn-submit-edit-action" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initCriteriaEditSelect2($context) {
            var $targets = $context ? $context.find('.select2-criteria-edit') : $('.select2-criteria-edit');
            $targets.each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: 'Chọn chỉ tiêu...',
                        allowClear: true,
                        dropdownParent: $(this).closest('.modal')
                    });
                }
            });
        }

        $('[id^="requestEditModal_"]').on('shown.bs.modal', function() {
            initCriteriaEditSelect2($(this));
        });

        // Set action type on edit form
        $(document).on('click', '.btn-submit-edit-action', function() {
            var action = $(this).data('action') || 'draft';
            var $form = $(this).closest('form');
            $form.find('.edit-action-type').val(action);
        });

        // Add row in edit modal
        $(document).on('click', '.btn-add-edit-req-row', function() {
            var modalId = $(this).data('modal');
            var $tbody = $(modalId).find('table.table-edit-req-rows tbody');
            var $firstRow = $tbody.find('tr:first');
            var newIdx = $tbody.find('tr').length;

            // Destroy select2 on rows before clone
            $tbody.find('.select2-criteria-edit').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            var $newRow = $firstRow.clone();

            $newRow.find('select, input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + newIdx + ']'));
                }
                $(this).val(null);
            });

            $newRow.find('.cell-specification').text('—');
            $newRow.find('.cell-supplier').text('—');
            $newRow.find('.cell-purpose').text('—');

            $newRow.find('.btn-remove-edit-req-row').prop('disabled', false);
            $tbody.append($newRow);

            // Enable delete buttons if > 1 row
            $tbody.find('.btn-remove-edit-req-row').prop('disabled', false);

            initCriteriaEditSelect2($tbody);
        });

        // Remove row in edit modal
        $(document).on('click', '.btn-remove-edit-req-row', function() {
            var $tbody = $(this).closest('tbody');
            if ($tbody.find('tr').length > 1) {
                $(this).closest('tr').remove();
                if ($tbody.find('tr').length <= 1) {
                    $tbody.find('.btn-remove-edit-req-row').prop('disabled', true);
                }
            }
        });
    });
</script>
