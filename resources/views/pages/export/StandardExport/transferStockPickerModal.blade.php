{{--
| Bảng chọn nhiều chất chuẩn từ DANH MỤC CỦA PHÒNG ĐƯỢC ĐỀ NGHỊ (phòng sẽ cấp phát).
|
| Phòng nguồn do người dùng chọn ngay trên form đề nghị nên bảng nạp bằng AJAX theo phòng
| đang chọn, xem StandardExportController::transferDepartmentStock.
--}}
<div class="modal fade md-modal" id="stdStockPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 92vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-vial mr-2"></i> Danh Mục Chất Chuẩn Của Phòng
                    <span id="stdStockPickerDept" class="text-dark"></span>
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
                            <input type="text" id="stdStockSearchInput" class="form-control"
                                placeholder="Tìm theo mã chất chuẩn, tên chất chuẩn, số CAS, nhà sản xuất...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="stdStockFilter" class="form-control">
                            <option value="all" selected>Tất cả danh mục chất chuẩn</option>
                            <option value="in_stock">Chỉ chất chuẩn còn tồn kho (&gt; 0)</option>
                            <option value="out_of_stock">Chất chuẩn hết tồn kho (= 0)</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-right">
                        <span class="badge badge-info px-2 py-2" id="stdStockVisibleCount" style="font-size: 0.85rem;">0 chất chuẩn</span>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 58vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="stdStockPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 46px"><input type="checkbox" id="stdStockCheckAll" class="pick-check" title="Chọn tất cả"></th>
                                <th style="width: 45px">STT</th>
                                <th style="width: 130px">Mã Chất Chuẩn</th>
                                <th style="min-width: 220px">Tên Chất Chuẩn</th>
                                <th style="min-width: 130px">Số CAS</th>
                                <th style="min-width: 160px">Nhà Sản Xuất</th>
                                <th style="min-width: 150px">Điều Kiện Bảo Quản</th>
                                <th style="width: 170px" class="text-right">Tồn Kho Phòng Nguồn</th>
                                <th style="width: 80px" class="text-center">ĐVT</th>
                            </tr>
                        </thead>
                        <tbody id="stdStockPickerBody">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chọn phòng ban đang giữ chuẩn để xem danh mục.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-primary px-3 py-2" id="stdStockSelectedCount" style="font-size: 0.88rem;">
                    <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 chất chuẩn
                </span>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="stdStockConfirmBtn">
                        <i class="fas fa-plus-circle mr-1"></i> Thêm vào đề nghị
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var stockUrl = "{{ route('pages.export.standardExport.transferDepartmentStock') }}";
        var targetRowsSelector = null;

        /** Bỏ số 0 thừa ở phần thập phân: 12.5000 -> 12.5 */
        function num(value) {
            return String(parseFloat(value || 0).toFixed(4)).replace(/\.?0+$/, '');
        }

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function updateSelectedCount() {
            var count = $('#stdStockPickerTable .std-stock-checkbox:checked').length;
            $('#stdStockSelectedCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' chất chuẩn');
        }

        function applyFilter() {
            var search = ($('#stdStockSearchInput').val() || '').toLowerCase().trim();
            var stockFilter = $('#stdStockFilter').val();
            var visible = 0;

            $('#stdStockPickerBody tr.std-stock-row').each(function () {
                var haystack = [
                    $(this).data('code'), $(this).data('name'),
                    $(this).data('cas'), $(this).data('manufacturer-name')
                ].join(' ').toLowerCase();
                var stock = parseFloat($(this).data('stock')) || 0;

                var matchSearch = !search || haystack.indexOf(search) !== -1;
                var matchStock = stockFilter === 'in_stock' ? stock > 0
                    : (stockFilter === 'out_of_stock' ? stock <= 0 : true);

                if (matchSearch && matchStock) { $(this).show(); visible++; } else { $(this).hide(); }
            });

            $('#stdStockVisibleCount').text(visible + ' chất chuẩn');
        }

        function renderRows(rows) {
            var $body = $('#stdStockPickerBody').empty();

            if (!rows.length) {
                $body.append('<tr><td colspan="9" class="text-center text-muted py-4">' +
                    'Phòng ban này chưa khai chất chuẩn nào trong <b>Danh Mục &rarr; Chất Chuẩn Của Phòng</b>.</td></tr>');
                $('#stdStockVisibleCount').text('0 chất chuẩn');

                return;
            }

            rows.forEach(function (row, index) {
                var unit = row.unit || '';
                var code = row.code + (row.version ? ' v' + row.version : '');
                var stockCell = row.remaining > 0
                    ? '<span class="badge badge-success font-weight-bold px-2 py-1" style="font-size: 0.85rem;">' +
                        num(row.remaining) + ' ' + esc(unit) + '</span>' +
                        '<small class="text-muted d-block font-italic">(' + row.lots + ' ống)</small>'
                    : '<span class="badge badge-light text-muted border px-2 py-1">0 ' + esc(unit) + '</span>';

                $body.append(
                    '<tr class="std-stock-row"' +
                        ' data-category-id="' + row.id + '"' +
                        ' data-code="' + esc(code) + '"' +
                        ' data-name="' + esc(row.name) + '"' +
                        ' data-cas="' + esc(row.cas_no) + '"' +
                        ' data-manufacturer-name="' + esc(row.manufacturer_name) + '"' +
                        ' data-stock="' + row.remaining + '">' +
                        '<td class="text-center align-middle"><input type="checkbox" class="std-stock-checkbox pick-check" value="' + row.id + '"></td>' +
                        '<td class="text-center align-middle text-muted">' + (index + 1) + '</td>' +
                        '<td class="align-middle"><span class="badge badge-secondary px-2 py-1" style="font-size: 0.82rem;">' + (esc(code) || '—') + '</span></td>' +
                        '<td class="align-middle font-weight-bold text-dark">' + esc(row.name) + '</td>' +
                        '<td class="align-middle">' + (esc(row.cas_no) || '—') + '</td>' +
                        '<td class="align-middle">' + (esc(row.manufacturer_name) || '—') + '</td>' +
                        '<td class="align-middle">' + (esc(row.storage_condition_name) || '—') + '</td>' +
                        '<td class="text-right align-middle">' + stockCell + '</td>' +
                        '<td class="text-center align-middle">' + (esc(unit) || '—') + '</td>' +
                    '</tr>'
                );
            });

            applyFilter();
        }

        // Nút "Danh mục chất chuẩn phòng nguồn" trên form tạo / điều chỉnh đề nghị
        $(document).on('click', '.btn-open-std-stock-picker', function () {
            var $form = $(this).closest('form');
            var departmentId = $form.find('select[name="to_department_id"]').val();
            var departmentLabel = $form.find('select[name="to_department_id"] option:selected').text().trim();

            if (!departmentId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn phòng ban',
                    text: 'Vui lòng chọn phòng ban đang giữ chuẩn trước, danh mục sẽ hiện theo kho của phòng đó.',
                    confirmButtonText: 'Đã hiểu'
                });

                return;
            }

            targetRowsSelector = $(this).data('target-rows');

            $('#stdStockPickerDept').text('— ' + departmentLabel);
            $('#stdStockCheckAll').prop('checked', false);
            $('#stdStockSearchInput').val('');
            $('#stdStockFilter').val('all');
            $('#stdStockPickerBody').html('<tr><td colspan="9" class="text-center text-muted py-4">' +
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải danh mục chất chuẩn của phòng...</td></tr>');
            updateSelectedCount();
            $('#stdStockPickerModal').modal('show');

            $.get(stockUrl, { department_id: departmentId })
                .done(function (res) {
                    if (!res.ok) {
                        $('#stdStockPickerBody').html('<tr><td colspan="9" class="text-center text-danger py-4">' +
                            esc(res.message) + '</td></tr>');

                        return;
                    }

                    renderRows(res.rows || []);
                })
                .fail(function () {
                    $('#stdStockPickerBody').html('<tr><td colspan="9" class="text-center text-danger py-4">' +
                        'Không tải được danh mục chất chuẩn của phòng, vui lòng thử lại.</td></tr>');
                });
        });

        $('#stdStockSearchInput').on('input', applyFilter);
        $('#stdStockFilter').on('change', applyFilter);

        $('#stdStockCheckAll').on('change', function () {
            $('#stdStockPickerBody tr.std-stock-row:visible .std-stock-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.std-stock-checkbox', updateSelectedCount);

        // Bấm vào bất kỳ đâu trên dòng cũng tick được, đỡ phải nhắm đúng ô vuông
        $(document).on('click', '#stdStockPickerBody tr.std-stock-row', function (e) {
            if ($(e.target).is('input.std-stock-checkbox')) {
                return;
            }

            var $box = $(this).find('.std-stock-checkbox');
            $box.prop('checked', !$box.prop('checked'));
            updateSelectedCount();
        });

        $('#stdStockConfirmBtn').on('click', function () {
            var $selected = $('#stdStockPickerBody tr.std-stock-row').has('.std-stock-checkbox:checked');

            if (!$selected.length) {
                Swal.fire({ icon: 'warning', title: 'Chưa chọn chất chuẩn', text: 'Vui lòng chọn ít nhất một chất chuẩn trong danh mục!' });

                return;
            }

            var $tbody = $(targetRowsSelector);

            $selected.each(function () {
                var catId = String($(this).data('category-id'));

                // Đã có dòng cho chất chuẩn này thì bỏ qua, không thêm trùng
                if ($tbody.find('.select-transfer-category').filter(function () { return $(this).val() === catId; }).length) {
                    return;
                }

                // Dòng còn trống thì điền vào, hết dòng trống mới thêm dòng mới
                var $row = $tbody.find('tr').filter(function () {
                    return !$(this).find('.select-transfer-category').val();
                }).first();

                if (!$row.length) {
                    $row = window.stdAddTransferRow($tbody);
                }

                $row.find('.select-transfer-category').val(catId).trigger('change');
            });

            $('#stdStockPickerModal').modal('hide');
        });
    });
</script>
