{{--
| Bảng chọn nhiều vật tư từ DANH MỤC CỦA PHÒNG ĐƯỢC ĐỀ NGHỊ (phòng sẽ cấp phát).
|
| Khác picker của tab đề nghị nội bộ (inventoryPickerModal - đọc kho phòng mình): ở đây
| phòng nguồn do người dùng chọn ngay trên form nên bảng nạp bằng AJAX theo phòng đang
| chọn, xem MaterialExportController::transferDepartmentStock.
--}}
<div class="modal fade md-modal" id="matStockPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes-stacked mr-2"></i> Danh Mục Vật Tư Của Phòng
                    <span id="matStockPickerDept" class="text-dark"></span>
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
                            <input type="text" id="matStockSearchInput" class="form-control"
                                placeholder="Tìm theo mã vật tư, tên vật tư, quy cách, nhà sản xuất...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="matStockFilter" class="form-control">
                            <option value="all" selected>Tất cả danh mục vật tư</option>
                            <option value="in_stock">Chỉ vật tư còn tồn kho (&gt; 0)</option>
                            <option value="out_of_stock">Vật tư hết tồn kho (= 0)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <span class="badge badge-info px-2 py-2" id="matStockVisibleCount" style="font-size: 0.85rem;">0 vật tư</span>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 58vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="matStockPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 46px"><input type="checkbox" id="matStockCheckAll" class="pick-check" title="Chọn tất cả"></th>
                                <th style="width: 45px">STT</th>
                                <th style="width: 110px">Mã Vật Tư</th>
                                <th style="min-width: 220px">Tên Vật Tư</th>
                                <th style="min-width: 180px">Quy Cách</th>
                                <th style="min-width: 160px">Nhà Sản Xuất</th>
                                <th style="min-width: 140px">Phân Loại</th>
                                <th style="width: 170px" class="text-right">Tồn Kho Phòng Nguồn</th>
                                <th style="width: 80px" class="text-center">ĐVT</th>
                            </tr>
                        </thead>
                        <tbody id="matStockPickerBody">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chọn phòng ban đang giữ vật tư để xem danh mục.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-primary px-3 py-2" id="matStockSelectedCount" style="font-size: 0.88rem;">
                    <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 vật tư
                </span>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="matStockConfirmBtn">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm vào đề nghị
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var stockUrl = "{{ route('pages.export.materialExport.transferDepartmentStock') }}";
        var targetRowsSelector = null;

        /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
        function num(value) {
            return String(parseFloat(value || 0).toFixed(4)).replace(/\.?0+$/, '');
        }

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function updateSelectedCount() {
            var count = $('#matStockPickerTable .mat-stock-checkbox:checked').length;
            $('#matStockSelectedCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' vật tư');
        }

        function applyFilter() {
            var search = ($('#matStockSearchInput').val() || '').toLowerCase().trim();
            var stockFilter = $('#matStockFilter').val();
            var visible = 0;

            $('#matStockPickerBody tr.mat-stock-row').each(function () {
                var haystack = [
                    $(this).data('code'), $(this).data('name'),
                    $(this).data('specification'), $(this).data('manufacturer-name')
                ].join(' ').toLowerCase();
                var stock = parseFloat($(this).data('stock')) || 0;

                var matchSearch = !search || haystack.indexOf(search) !== -1;
                var matchStock = stockFilter === 'in_stock' ? stock > 0
                    : (stockFilter === 'out_of_stock' ? stock <= 0 : true);

                if (matchSearch && matchStock) { $(this).show(); visible++; } else { $(this).hide(); }
            });

            $('#matStockVisibleCount').text(visible + ' vật tư');
        }

        function renderRows(rows) {
            var $body = $('#matStockPickerBody').empty();

            if (!rows.length) {
                $body.append('<tr><td colspan="9" class="text-center text-muted py-4">' +
                    'Phòng ban này chưa khai vật tư nào trong <b>Danh Mục &rarr; Vật Tư Của Phòng</b>.</td></tr>');
                $('#matStockVisibleCount').text('0 vật tư');

                return;
            }

            rows.forEach(function (row, index) {
                var unit = row.unit || '';
                var stockCell = row.remaining > 0
                    ? '<span class="badge badge-success font-weight-bold px-2 py-1" style="font-size: 0.85rem;">' +
                        num(row.remaining) + ' ' + esc(unit) + '</span>' +
                        '<small class="text-muted d-block font-italic">(' + row.lots + ' mã xuất nhập)</small>'
                    : '<span class="badge badge-light text-muted border px-2 py-1">0 ' + esc(unit) + '</span>';

                $body.append(
                    '<tr class="mat-stock-row"' +
                        ' data-category-id="' + row.id + '"' +
                        ' data-code="' + esc(row.code) + '"' +
                        ' data-name="' + esc(row.material_name) + '"' +
                        ' data-specification="' + esc(row.technical_specification) + '"' +
                        ' data-manufacturer-name="' + esc(row.manufacturer_name) + '"' +
                        ' data-stock="' + row.remaining + '">' +
                        '<td class="text-center align-middle"><input type="checkbox" class="mat-stock-checkbox pick-check" value="' + row.id + '"></td>' +
                        '<td class="text-center align-middle text-muted">' + (index + 1) + '</td>' +
                        '<td class="align-middle"><span class="badge badge-secondary px-2 py-1" style="font-size: 0.82rem;">' + (esc(row.code) || '—') + '</span></td>' +
                        '<td class="align-middle font-weight-bold text-dark">' + esc(row.material_name) + '</td>' +
                        '<td class="align-middle">' + (esc(row.technical_specification) || '—') + '</td>' +
                        '<td class="align-middle">' + (esc(row.manufacturer_name) || '—') + '</td>' +
                        '<td class="align-middle">' + (esc(row.classification_name) || '—') + '</td>' +
                        '<td class="text-right align-middle">' + stockCell + '</td>' +
                        '<td class="text-center align-middle">' + (esc(unit) || '—') + '</td>' +
                    '</tr>'
                );
            });

            applyFilter();
        }

        // Nút "Danh mục vật tư phòng nguồn" trên form tạo / điều chỉnh đề nghị
        $(document).on('click', '.btn-open-mat-stock-picker', function () {
            var $form = $(this).closest('form');
            var departmentId = $form.find('select[name="to_department_id"]').val();
            var departmentLabel = $form.find('select[name="to_department_id"] option:selected').text().trim();

            if (!departmentId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn phòng ban',
                    text: 'Vui lòng chọn phòng ban đang giữ vật tư trước, danh mục sẽ hiện theo kho của phòng đó.',
                    confirmButtonText: 'Đã hiểu'
                });

                return;
            }

            targetRowsSelector = $(this).data('target-rows');

            $('#matStockPickerDept').text('— ' + departmentLabel);
            $('#matStockCheckAll').prop('checked', false);
            $('#matStockSearchInput').val('');
            $('#matStockFilter').val('all');
            $('#matStockPickerBody').html('<tr><td colspan="9" class="text-center text-muted py-4">' +
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải danh mục vật tư của phòng...</td></tr>');
            updateSelectedCount();
            $('#matStockPickerModal').modal('show');

            $.get(stockUrl, { department_id: departmentId })
                .done(function (res) {
                    if (!res.ok) {
                        $('#matStockPickerBody').html('<tr><td colspan="9" class="text-center text-danger py-4">' +
                            esc(res.message) + '</td></tr>');

                        return;
                    }

                    renderRows(res.rows || []);
                })
                .fail(function () {
                    $('#matStockPickerBody').html('<tr><td colspan="9" class="text-center text-danger py-4">' +
                        'Không tải được danh mục vật tư của phòng, vui lòng thử lại.</td></tr>');
                });
        });

        $('#matStockSearchInput').on('input', applyFilter);
        $('#matStockFilter').on('change', applyFilter);

        $('#matStockCheckAll').on('change', function () {
            $('#matStockPickerBody tr.mat-stock-row:visible .mat-stock-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.mat-stock-checkbox', updateSelectedCount);

        // Bấm vào bất kỳ đâu trên dòng cũng tick được, đỡ phải nhắm đúng ô vuông
        $(document).on('click', '#matStockPickerBody tr.mat-stock-row', function (e) {
            if ($(e.target).is('input.mat-stock-checkbox')) {
                return;
            }

            var $box = $(this).find('.mat-stock-checkbox');
            $box.prop('checked', !$box.prop('checked'));
            updateSelectedCount();
        });

        $('#matStockConfirmBtn').on('click', function () {
            var $selected = $('#matStockPickerBody tr.mat-stock-row').has('.mat-stock-checkbox:checked');

            if (!$selected.length) {
                Swal.fire({ icon: 'warning', title: 'Chưa chọn vật tư', text: 'Vui lòng chọn ít nhất một vật tư trong danh mục!' });

                return;
            }

            var $tbody = $(targetRowsSelector);

            $selected.each(function () {
                var catId = String($(this).data('category-id'));

                // Đã có dòng cho vật tư này thì bỏ qua, không thêm trùng
                if ($tbody.find('.select-mat-transfer-category').filter(function () { return $(this).val() === catId; }).length) {
                    return;
                }

                // Dòng còn trống thì điền vào, hết dòng trống mới thêm dòng mới
                var $row = $tbody.find('tr').filter(function () {
                    return !$(this).find('.select-mat-transfer-category').val();
                }).first();

                if (!$row.length) {
                    $row = window.matAddTransferRow($tbody);
                }

                $row.find('.select-mat-transfer-category').val(catId).trigger('change');
            });

            $('#matStockPickerModal').modal('hide');
        });
    });
</script>
