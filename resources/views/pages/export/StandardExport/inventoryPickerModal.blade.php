<div class="modal fade md-modal" id="stdInventoryPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes-stacked mr-2"></i> Danh Mục Chuẩn Tồn Của Phòng
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">
                {{-- Filter & Search Toolbar --}}
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="pickerSearchInput" class="form-control" placeholder="Tìm theo mã chuẩn, tên chuẩn, nhà sản xuất...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="pickerStockFilter" class="form-control">
                            <option value="all" selected>Tất cả danh mục chuẩn</option>
                            <option value="in_stock">Chỉ chất chuẩn còn tồn kho (> 0)</option>
                            <option value="out_of_stock">Chất chuẩn hết tồn kho (= 0)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <span class="badge badge-info px-2 py-2" id="pickerVisibleCount" style="font-size: 0.85rem;">
                            {{ count($departmentStandardInventory ?? []) }} chất chuẩn
                        </span>
                    </div>
                </div>

                {{-- Table of Standards --}}
                <div class="table-responsive border rounded" style="max-height: 58vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="pickerStandardsTable" style="font-size: 0.88rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 40px">
                                    <input type="checkbox" id="pickerCheckAll" title="Chọn tất cả">
                                </th>
                                <th style="width: 45px">STT</th>
                                <th style="width: 130px">Mã Chuẩn</th>
                                <th style="min-width: 220px">Tên Chất Chuẩn</th>
                                <th style="min-width: 120px">Qui Cách</th>
                                <th style="min-width: 160px">Nhà Sản Xuất</th>
                                <th style="min-width: 150px">Mục Đích</th>
                                <th style="width: 160px" class="text-right">Tồn Kho Phòng</th>
                                <th style="width: 80px" class="text-center">ĐVT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departmentStandardInventory ?? [] as $std)
                                @php
                                    $hasStock = (float)$std->total_remaining > 0;
                                    $unitLabel = $std->unit_short_name ?: $std->unit_name;
                                @endphp
                                <tr class="picker-row {{ $hasStock ? 'has-stock' : 'no-stock' }}"
                                    data-category-id="{{ $std->id }}"
                                    data-code="{{ $std->code }}"
                                    data-version="{{ $std->version }}"
                                    data-name="{{ $std->standard_name }}"
                                    data-specification="{{ $std->specification ?: '' }}"
                                    data-manufacturer-id="{{ $std->manufacturer_id ?: '' }}"
                                    data-manufacturer-name="{{ $std->manufacturer_name ?: '' }}"
                                    data-purpose-id="{{ $std->purpose_id ?: '' }}"
                                    data-purpose-name="{{ $std->purpose_name ?: '' }}"
                                    data-unit="{{ $unitLabel ?: '' }}"
                                    data-criteria="{{ json_encode($std->criteria_names ?? []) }}"
                                    data-stock="{{ (float)$std->total_remaining }}">
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="picker-item-checkbox" value="{{ $std->id }}">
                                    </td>
                                    <td class="text-center align-middle text-muted picker-stt">{{ $loop->iteration }}</td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-secondary px-2 py-1">{{ $std->code }} v{{ $std->version }}</span>
                                    </td>
                                    <td class="align-middle font-weight-bold text-dark">{{ $std->standard_name }}</td>
                                    <td class="text-center align-middle">{{ $std->specification ?: '—' }}</td>
                                    <td class="align-middle">{{ $std->manufacturer_name ?: '—' }}</td>
                                    <td class="align-middle">{{ $std->purpose_name ?: '—' }}</td>
                                    <td class="text-right align-middle">
                                        @if ($hasStock)
                                            <span class="badge badge-success font-weight-bold px-2 py-1" style="font-size: 0.85rem;">
                                                {{ $expNum($std->total_remaining) }} {{ $unitLabel }}
                                            </span>
                                            <small class="text-muted d-block font-italic">({{ $std->total_tubes }} ống)</small>
                                        @else
                                            <span class="badge badge-light text-muted border px-2 py-1">0 {{ $unitLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle font-weight-500">{{ $unitLabel ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Chưa có danh mục chất chuẩn nào trong phòng ban này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge badge-primary px-3 py-2" id="pickerSelectedCount" style="font-size: 0.88rem;">
                        <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 chất chuẩn
                    </span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmAddStandards">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm vào đề nghị
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var targetFormModalId = '#requestModal';

        // When opening picker modal, remember which form opened it
        $(document).on('click', '.btn-open-inventory-picker', function() {
            targetFormModalId = $(this).data('target-modal') || '#requestModal';
            // Uncheck all and apply initial filter
            $('#pickerCheckAll').prop('checked', false);
            $('.picker-item-checkbox').prop('checked', false);
            updatePickerSelectedCount();
            applyPickerFilter();
            $('#stdInventoryPickerModal').modal('show');
        });

        // Filter & Search handler
        function applyPickerFilter() {
            var search = ($('#pickerSearchInput').val() || '').toLowerCase().trim();
            var stockFilter = $('#pickerStockFilter').val();
            var visible = 0;

            $('#pickerStandardsTable tbody tr.picker-row').each(function() {
                var name = ($(this).data('name') || '').toString().toLowerCase();
                var code = ($(this).data('code') || '').toString().toLowerCase();
                var manufacturer = ($(this).data('manufacturer-name') || '').toString().toLowerCase();
                var stock = parseFloat($(this).data('stock')) || 0;

                var matchSearch = !search || name.indexOf(search) !== -1 || code.indexOf(search) !== -1 || manufacturer.indexOf(search) !== -1;
                var matchStock = true;
                if (stockFilter === 'in_stock') {
                    matchStock = stock > 0;
                } else if (stockFilter === 'out_of_stock') {
                    matchStock = stock <= 0;
                }

                if (matchSearch && matchStock) {
                    $(this).show();
                    visible++;
                } else {
                    $(this).hide();
                }
            });

            $('#pickerVisibleCount').text(visible + ' chất chuẩn');
        }

        $('#pickerSearchInput').on('input', applyPickerFilter);
        $('#pickerStockFilter').on('change', applyPickerFilter);

        // Check all visible rows
        $('#pickerCheckAll').on('change', function() {
            var checked = $(this).is(':checked');
            $('#pickerStandardsTable tbody tr.picker-row:visible .picker-item-checkbox').prop('checked', checked);
            updatePickerSelectedCount();
        });

        $(document).on('change', '.picker-item-checkbox', function() {
            updatePickerSelectedCount();
        });

        function updatePickerSelectedCount() {
            var count = $('.picker-item-checkbox:checked').length;
            $('#pickerSelectedCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' chất chuẩn');
        }

        // Add selected standards to target request form
        $('#btnConfirmAddStandards').on('click', function() {
            var $selectedRows = $('#pickerStandardsTable tbody tr.picker-row').has('.picker-item-checkbox:checked');
            if ($selectedRows.length === 0) {
                alert('Vui lòng chọn ít nhất một chất chuẩn từ danh mục!');
                return;
            }

            var $targetModal = $(targetFormModalId);
            var isEditModal = targetFormModalId.indexOf('requestEditModal') !== -1;
            var $tbody = isEditModal ? $targetModal.find('table.table-edit-req-rows tbody') : $targetModal.find('#tableReqRows tbody');

            $selectedRows.each(function() {
                var $pRow = $(this);
                var catId = $pRow.data('category-id');
                var catName = $pRow.data('name');
                var code = $pRow.data('code');
                var spec = $pRow.data('specification') || '';
                var mfgId = $pRow.data('manufacturer-id') || '';
                var mfgName = $pRow.data('manufacturer-name') || '';
                var purId = $pRow.data('purpose-id') || '';
                var purName = $pRow.data('purpose-name') || '';
                var unit = $pRow.data('unit') || '';
                var criteria = $pRow.data('criteria') || [];

                // Check if first row is empty/placeholder
                var $firstRow = $tbody.find('tr.req-row:first');
                var firstCatVal = $firstRow.find('.select-req-category').val();

                var $rowToPopulate;
                if ($tbody.find('tr.req-row').length === 1 && !firstCatVal) {
                    $rowToPopulate = $firstRow;
                } else {
                    // Clone a new row
                    var newIdx = $tbody.find('tr.req-row').length;
                    
                    // Destroy select2 before cloning
                    $tbody.find('.select2-criteria, .select2-criteria-edit').each(function() {
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
                    $tbody.append($newRow);
                    $rowToPopulate = $newRow;
                }

                // Fill data into row
                $rowToPopulate.find('.select-req-category').val(catId);
                $rowToPopulate.find('.cell-specification').text(spec || '—');
                $rowToPopulate.find('.input-req-specification').val(spec);
                $rowToPopulate.find('.cell-supplier').text(mfgName || '—');
                $rowToPopulate.find('.input-req-supplier').val(mfgId);
                $rowToPopulate.find('.cell-purpose').text(purName || '—');
                $rowToPopulate.find('.input-req-purpose').val(purId);
                if (unit) {
                    $rowToPopulate.find('.select-req-unit').val(unit);
                }

                // Populate criteria
                var $crit = $rowToPopulate.find('.select2-criteria, .select2-criteria-edit');
                if (criteria && criteria.length > 0) {
                    $crit.val(criteria);
                }

                // Enable remove buttons
                $tbody.find('.btn-remove-req-row, .btn-remove-edit-req-row').prop('disabled', false);
            });

            // Re-init select2 on all rows
            $tbody.find('.select2-criteria, .select2-criteria-edit').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: 'Chọn chỉ tiêu...',
                        allowClear: true,
                        dropdownParent: $targetModal
                    });
                }
            });

            // Close picker modal
            $('#stdInventoryPickerModal').modal('hide');
        });
    });
</script>
