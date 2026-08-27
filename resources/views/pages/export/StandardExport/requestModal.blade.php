<div class="modal fade md-modal" id="requestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 90%; width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane mr-1"></i> Tạo Đề Nghị Cấp Phát Chuẩn Cho Tổ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.standardExport.requestStore') }}" method="POST" autocomplete="off" id="formStdRequest">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" name="action_type" id="requestActionType" value="send">
                    
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
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-info mr-2 btn-open-inventory-picker shadow-sm" data-target-modal="#requestModal">
                                <i class="fas fa-boxes-stacked mr-1"></i> Danh mục chuẩn tồn của phòng
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-req-row shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Thêm chất chuẩn
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        <style>
                            #tableReqRows td {
                                vertical-align: middle !important;
                                padding: 8px !important;
                            }
                            #tableReqRows th {
                                white-space: nowrap;
                                vertical-align: middle;
                                background-color: #f1f5f9;
                                font-size: 0.88rem;
                                padding: 10px 8px !important;
                            }
                            #tableReqRows .form-control {
                                min-height: 38px !important;
                                height: 38px !important;
                                font-size: 0.9rem !important;
                                line-height: 1.5 !important;
                                padding: 6px 10px !important;
                            }
                            #tableReqRows textarea.auto-resize {
                                resize: vertical;
                                min-height: 38px !important;
                                height: 38px !important;
                                line-height: 1.4 !important;
                                overflow-y: auto;
                            }
                            #tableReqRows .select2-container--bootstrap4 .select2-selection--multiple {
                                min-height: 38px !important;
                                height: auto !important;
                                padding: 3px 6px !important;
                                font-size: 0.9rem !important;
                            }
                            #tableReqRows .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
                                margin-top: 3px !important;
                                margin-bottom: 3px !important;
                                font-size: 0.84rem !important;
                                line-height: 1.4 !important;
                            }
                            #tableReqRows .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
                                height: 28px !important;
                                margin-top: 3px !important;
                            }
                        </style>
                        <table class="table table-bordered mb-0" id="tableReqRows" style="font-size: 0.9rem;">
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
                                <tr class="req-row">
                                    <td class="text-center align-middle" style="background-color: #f8fafc;">
                                        <span class="text-muted small font-italic">(Cấp sau)</span>
                                    </td>
                                    <td>
                                        <select name="items[0][category_id]" class="form-control select-req-category" required>
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
                                        <input type="number" step="0.0001" min="0.0001" name="items[0][requested_amount]" class="form-control text-right" placeholder="0.0000" required>
                                    </td>
                                    <td>
                                        <select name="items[0][requested_unit]" class="form-control select-req-unit">
                                            <option value="">-- ĐVT --</option>
                                            @foreach ($units as $u)
                                                @php $uVal = $u->short_name ?: $u->name; @endphp
                                                <option value="{{ $uVal }}">{{ $uVal }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" list="productList" name="items[0][product_name]" class="form-control" placeholder="Tên SP...">
                                    </td>
                                    <td style="min-width: 220px;">
                                        <select name="items[0][test_criteria][]" class="form-control select2-criteria" multiple="multiple">
                                            @foreach ($purposes as $purpose)
                                                <option value="{{ $purpose->name }}">{{ $purpose->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[0][analyst_id]" class="form-control">
                                            <option value="">-- KNV --</option>
                                            @foreach ($analysts as $an)
                                                <option value="{{ $an->id }}">{{ $an->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="items[0][note]" class="form-control auto-resize" rows="1" placeholder="Ghi chú..."></textarea>
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-xs btn-danger btn-remove-req-row" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                            <datalist id="productList">
                                @foreach ($productNames as $pn)
                                    <option value="{{ $pn->name }}">
                                @endforeach
                            </datalist>

                            <datalist id="packSpecList">
                                @foreach ($packagingSpecs as $ps)
                                    <option value="{{ $ps->name }}">
                                @endforeach
                                <option value="100 mg">
                                <option value="250 mg">
                                <option value="500 mg">
                                <option value="1 g">
                                <option value="20 ml">
                                <option value="250 ml">
                                <option value="500 ml">
                                <option value="1000 ml">
                            </datalist>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-outline-primary btn-submit-action" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <button type="submit" class="btn btn-primary btn-submit-action" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->requestCreateErrors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#requestModal').modal('show');
    });
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var rowIdx = 1;

        function initCriteriaSelect2($context) {
            var $targets = $context ? $context.find('.select2-criteria') : $('.select2-criteria');
            $targets.each(function() {
                if (!$(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: 'Chọn chỉ tiêu...',
                        allowClear: true,
                        dropdownParent: $('#requestModal')
                    });
                }
            });
        }

        $('#requestModal').on('shown.bs.modal', function() {
            initCriteriaSelect2($(this));
        });

        // Click handler for Lưu vs Gửi đề nghị
        $(document).on('click', '.btn-submit-action', function() {
            var action = $(this).data('action') || 'send';
            $('#requestActionType').val(action);
        });

        // Auto-resize textarea
        $(document).on('input', '.auto-resize', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        $(document).on('change', '.select-req-category', function() {
            var $row = $(this).closest('tr');
            var categoryId = $(this).val();
            var unit = $(this).find('option:selected').data('unit') || '';

            if (categoryId) {
                $.ajax({
                    url: '{{ route('pages.export.standardExport.getCategoryInfo') }}',
                    type: 'GET',
                    data: { category_id: categoryId },
                    success: function(res) {
                        if (res) {
                            $row.find('.cell-specification').text(res.specification || '—');
                            $row.find('.input-req-specification').val(res.specification === '—' ? '' : res.specification);

                            $row.find('.cell-supplier').text(res.supplier_name || '—');
                            $row.find('.input-req-supplier').val(res.supplier_id || '');

                            var finalUnit = res.unit && res.unit !== '—' ? res.unit : (unit || '');
                            if (finalUnit) {
                                $row.find('.select-req-unit').val(finalUnit);
                            }

                            $row.find('.cell-purpose').text(res.purpose_name || '—');
                            $row.find('.input-req-purpose').val(res.purpose_id || '');
                        }
                    }
                });
            } else {
                $row.find('.cell-specification').text('—');
                $row.find('.input-req-specification').val('');

                $row.find('.cell-supplier').text('—');
                $row.find('.input-req-supplier').val('');

                $row.find('.select-req-unit').val('');

                $row.find('.cell-purpose').text('—');
                $row.find('.input-req-purpose').val('');
            }
        });

        $('.btn-add-req-row').click(function() {
            var $tbody = $('#tableReqRows tbody');
            var $firstRow = $tbody.find('tr:first');

            // Destroy select2 before cloning
            $tbody.find('.select2-criteria').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            var $newRow = $firstRow.clone();

            $newRow.find('select, input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + rowIdx + ']'));
                }
                $(this).val(null);
            });

            $newRow.find('.cell-specification').text('—');
            $newRow.find('.cell-supplier').text('—');
            $newRow.find('.cell-purpose').text('—');

            $newRow.find('.btn-remove-req-row').prop('disabled', false);
            $tbody.append($newRow);
            rowIdx++;
            updateDeleteButtons();

            // Re-init select2 on all rows
            initCriteriaSelect2($tbody);
        });

        $(document).on('click', '.btn-remove-req-row', function() {
            if ($('#tableReqRows tbody tr').length > 1) {
                $(this).closest('tr').remove();
                updateDeleteButtons();
            }
        });

        function updateDeleteButtons() {
            var rows = $('#tableReqRows tbody tr');
            if (rows.length <= 1) {
                rows.find('.btn-remove-req-row').prop('disabled', true);
            } else {
                rows.find('.btn-remove-req-row').prop('disabled', false);
            }
        }
    });
</script>
