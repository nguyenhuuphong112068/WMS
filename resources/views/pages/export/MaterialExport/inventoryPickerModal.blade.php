{{-- Bảng chọn nhanh vật tư của phòng kèm tồn kho, đổ thẳng thành dòng của đề nghị cấp phát. --}}
<div class="modal fade md-modal" id="meInventoryPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes-stacked mr-2"></i> Danh Mục Vật Tư Tồn Của Phòng
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="mePickerSearchInput" class="form-control" placeholder="Tìm theo tên vật tư, quy cách, nhà sản xuất...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="mePickerStockFilter" class="form-control">
                            <option value="all" selected>Tất cả danh mục vật tư</option>
                            <option value="in_stock">Chỉ vật tư còn tồn kho (&gt; 0)</option>
                            <option value="out_of_stock">Vật tư hết tồn kho (= 0)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <span class="badge badge-info px-2 py-2" id="mePickerVisibleCount" style="font-size: 0.85rem;">
                            {{ count($departmentMaterialInventory ?? []) }} vật tư
                        </span>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 58vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="mePickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 40px"><input type="checkbox" id="mePickerCheckAll" title="Chọn tất cả"></th>
                                <th style="width: 45px">STT</th>
                                <th style="min-width: 220px">Tên Vật Tư</th>
                                <th style="min-width: 180px">Quy Cách</th>
                                <th style="min-width: 160px">Nhà Sản Xuất</th>
                                <th style="min-width: 140px">Phân Loại</th>
                                <th style="width: 170px" class="text-right">Tồn Kho Phòng</th>
                                <th style="width: 80px" class="text-center">ĐVT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($departmentMaterialInventory ?? [] as $mat)
                                @php
                                    $hasStock = (float) $mat->total_remaining > 0;
                                    $unitLabel = $mat->unit_short_name ?: $mat->unit_name;
                                @endphp
                                <tr class="me-picker-row"
                                    data-category-id="{{ $mat->id }}"
                                    data-name="{{ $mat->material_name }}"
                                    data-specification="{{ $mat->technical_specification ?: '' }}"
                                    data-manufacturer-name="{{ $mat->manufacturer_name ?: '' }}"
                                    data-unit="{{ $unitLabel ?: '' }}"
                                    data-stock="{{ (float) $mat->total_remaining }}">
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="me-picker-checkbox" value="{{ $mat->id }}">
                                    </td>
                                    <td class="text-center align-middle text-muted">{{ $loop->iteration }}</td>
                                    <td class="align-middle font-weight-bold text-dark">{{ $mat->material_name }}</td>
                                    <td class="align-middle">{{ $mat->technical_specification ?: '—' }}</td>
                                    <td class="align-middle">{{ $mat->manufacturer_name ?: '—' }}</td>
                                    <td class="align-middle">{{ $mat->classification_name ?: '—' }}</td>
                                    <td class="text-right align-middle">
                                        @if ($hasStock)
                                            <span class="badge badge-success font-weight-bold px-2 py-1" style="font-size: 0.85rem;">
                                                {{ $expNum($mat->total_remaining) }} {{ $unitLabel }}
                                            </span>
                                            <small class="text-muted d-block font-italic">({{ $mat->total_lots }} mã xuất nhập)</small>
                                        @else
                                            <span class="badge badge-light text-muted border px-2 py-1">0 {{ $unitLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">{{ $unitLabel ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Phòng chưa khai vật tư nào trong <b>Danh Mục &rarr; Vật Tư Của Phòng</b>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-primary px-3 py-2" id="mePickerSelectedCount" style="font-size: 0.88rem;">
                    <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 vật tư
                </span>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="meBtnConfirmAddMaterials">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm vào đề nghị
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var targetRowsSelector = '#reqCreateModal .me-rows';

        function updateSelectedCount() {
            var count = $('#mePickerTable .me-picker-checkbox:checked').length;
            $('#mePickerSelectedCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' vật tư');
        }

        function applyFilter() {
            var search = ($('#mePickerSearchInput').val() || '').toLowerCase().trim();
            var stockFilter = $('#mePickerStockFilter').val();
            var visible = 0;

            $('#mePickerTable tbody tr.me-picker-row').each(function () {
                var haystack = [
                    $(this).data('name'), $(this).data('specification'), $(this).data('manufacturer-name')
                ].join(' ').toLowerCase();
                var stock = parseFloat($(this).data('stock')) || 0;

                var matchSearch = !search || haystack.indexOf(search) !== -1;
                var matchStock = stockFilter === 'in_stock' ? stock > 0
                    : (stockFilter === 'out_of_stock' ? stock <= 0 : true);

                if (matchSearch && matchStock) { $(this).show(); visible++; } else { $(this).hide(); }
            });

            $('#mePickerVisibleCount').text(visible + ' vật tư');
        }

        // Nút "Danh mục tồn của phòng" trên form tạo / sửa đề nghị
        $(document).on('click', '.btn-open-me-picker', function () {
            targetRowsSelector = $(this).data('target-rows') || '#reqCreateModal .me-rows';
            $('#mePickerCheckAll').prop('checked', false);
            $('#mePickerTable .me-picker-checkbox').prop('checked', false);
            updateSelectedCount();
            applyFilter();
            $('#meInventoryPickerModal').modal('show');
        });

        $('#mePickerSearchInput').on('input', applyFilter);
        $('#mePickerStockFilter').on('change', applyFilter);

        $('#mePickerCheckAll').on('change', function () {
            $('#mePickerTable tbody tr.me-picker-row:visible .me-picker-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.me-picker-checkbox', updateSelectedCount);

        $('#meBtnConfirmAddMaterials').on('click', function () {
            var $selected = $('#mePickerTable tbody tr.me-picker-row').has('.me-picker-checkbox:checked');

            if (!$selected.length) {
                alert('Vui lòng chọn ít nhất một vật tư trong danh mục!');

                return;
            }

            var $tbody = $(targetRowsSelector);

            $selected.each(function () {
                var catId = String($(this).data('category-id'));

                // Đã có dòng cho vật tư này thì bỏ qua, không thêm trùng
                if ($tbody.find('.me-cat').filter(function () { return $(this).val() === catId; }).length) {
                    return;
                }

                // Dòng đầu còn trống thì điền vào, không thì thêm dòng mới
                var $row = $tbody.find('tr').filter(function () {
                    return !$(this).find('.me-cat').val() && !$(this).find('[name$="[material_name]"]').val();
                }).first();

                if (!$row.length) {
                    $row = window.meAddRequestRow($tbody);
                }

                $row.find('.me-cat').val(catId).trigger('change');
            });

            $('#meInventoryPickerModal').modal('hide');
        });
    });
</script>
