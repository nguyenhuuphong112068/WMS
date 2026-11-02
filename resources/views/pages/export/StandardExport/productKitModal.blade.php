{{--
|--------------------------------------------------------------------------
| ĐỀ NGHỊ CẤP PHÁT CHUẨN NỘI BỘ - THÊM THEO SẢN PHẨM - CHỈ TIÊU
|--------------------------------------------------------------------------
| Chọn một gói đã khai ở Danh Mục › Chất Chuẩn › tab Danh sách chất chuẩn theo sản phẩm
| (App\Support\ProductList), tick các chất chuẩn cần lấy rồi "Thêm Vào Đề Nghị": mỗi chất chuẩn
| thành một dòng của modal đang mở (#requestModal hoặc #requestEditModal_<id>) với Số Lượng ĐN
| = lượng gợi ý, Tên Sản Phẩm + Chỉ Tiêu theo gói. Quy cách / NSX / ĐVT / Mục đích tự nạp qua
| getCategoryInfo như khi chọn tay. Dòng đã có cùng chất chuẩn + cùng sản phẩm thì bỏ qua.
|
| Xếp chồng lên modal đề nghị như inventoryPickerModal (z-index tĩnh, trả modal-open khi đóng).
--}}
@php
    $stdKits = $productLists->map(fn($list) => [
        'id' => (string) $list->id,
        'text' => \App\Support\ProductList::label($list),
        'product_name' => $list->product_name,
        'purpose_name' => $list->purpose_name,
        'items' => $list->items->map(fn($item) => [
            'category_id' => (string) $item->category_id,
            'code' => $item->category_code,
            'name' => $item->item_name,
            'manufacturer' => $item->manufacturer_name,
            'amount' => \App\Support\ProductList::amount($item->amount),
            'unit' => $item->unit_short_name,
        ])->values(),
    ])->values();
@endphp

<style>
    #stdKitPickerModal .std-kit-table td {
        vertical-align: middle;
    }

    #stdKitPickerModal .std-kit-empty {
        padding: 22px 16px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--bg-neutral);
        color: #64748B;
        text-align: center;
    }
</style>

<div class="modal fade md-modal" id="stdKitPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 860px;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-box-open mr-2"></i> Thêm Theo Sản Phẩm - Chỉ Tiêu
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">
                <div class="form-group">
                    <label class="font-weight-bold">Sản Phẩm - Chỉ Tiêu</label>
                    <select class="form-control std-kit-select" {{ $stdKits->isEmpty() ? 'disabled' : '' }}>
                        <option value="">{{ $stdKits->isEmpty() ? 'Phòng chưa khai sản phẩm - chỉ tiêu' : '-- Chọn sản phẩm - chỉ tiêu --' }}</option>
                    </select>
                </div>

                <div class="table-responsive border rounded std-kit-wrap" style="display: none">
                    <table class="table table-sm table-bordered table-hover mb-0 std-kit-table" style="font-size: 0.88rem;" data-no-datatable>
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" style="width: 40px"><input type="checkbox" class="std-kit-check-all" checked title="Chọn tất cả"></th>
                                <th style="width: 100px">Mã DM</th>
                                <th>Chất Chuẩn</th>
                                <th style="width: 150px">Nhà Sản Xuất</th>
                                <th class="text-right" style="width: 150px">Số Lượng ĐN Gợi Ý</th>
                            </tr>
                        </thead>
                        <tbody class="std-kit-body"></tbody>
                    </table>
                </div>
                <div class="std-kit-empty">
                    <i class="fas fa-box-open mr-1"></i>
                    @if ($stdKits->isEmpty())
                        Khai gói ở Danh Mục › Chất Chuẩn › tab Danh sách chất chuẩn theo sản phẩm.
                    @else
                        Chọn sản phẩm - chỉ tiêu để xem các chất chuẩn của gói.
                    @endif
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-primary std-kit-confirm" disabled>
                    <i class="fas fa-plus mr-1"></i> Thêm Vào Đề Nghị
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var kits = @json($stdKits);
        var $modal = $('#stdKitPickerModal');
        var $select = $modal.find('.std-kit-select');
        var targetModalId = '#requestModal';
        var currentKit = null;

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function renderKit(kit) {
            currentKit = kit;
            $modal.find('.std-kit-body').html((kit ? kit.items : []).map(function(item, i) {
                return '<tr>' +
                    '<td class="text-center"><input type="checkbox" class="std-kit-check" value="' + i + '" checked></td>' +
                    '<td><span class="md-tag">' + esc(item.code || '—') + '</span></td>' +
                    '<td class="font-weight-bold">' + esc(item.name || '—') + '</td>' +
                    '<td>' + esc(item.manufacturer || '—') + '</td>' +
                    '<td class="text-right">' + (item.amount ? '<b>' + esc(item.amount) + '</b> ' + esc(item.unit || '') : '<span class="text-muted">Tự nhập</span>') + '</td>' +
                    '</tr>';
            }).join(''));

            $modal.find('.std-kit-check-all').prop('checked', true);
            $modal.find('.std-kit-wrap').toggle(!!kit);
            $modal.find('.std-kit-empty').toggle(!kit);
            $modal.find('.std-kit-confirm').prop('disabled', !kit);
        }

        if (kits.length) {
            $select.select2($.extend({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $modal,
                placeholder: $select.find('option[value=""]').text(),
                language: {
                    noResults: function() {
                        return 'Không tìm thấy sản phẩm - chỉ tiêu phù hợp';
                    }
                }
            }, { ajax: window.wmsLocalLookup(kits) }));

            $select.on('select2:select', function(e) {
                renderKit(kits.filter(function(kit) {
                    return kit.id === String(e.params.data.id);
                })[0] || null);
            });
        }

        $(document).on('click', '.btn-open-std-kit', function() {
            targetModalId = $(this).data('target-modal') || '#requestModal';

            if (kits.length) $select.val(null).trigger('change');
            renderKit(null);
            $modal.modal('show');
        });

        $modal.on('change', '.std-kit-check-all', function() {
            $modal.find('.std-kit-check').prop('checked', this.checked);
        });

        // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để modal đề nghị còn cuộn được
        $modal.on('hidden.bs.modal', function() {
            if ($('.modal.show').length) $('body').addClass('modal-open');
        });

        /** Một dòng trống để điền: dùng lại dòng đầu nếu bảng chỉ có nó và chưa chọn chất chuẩn, không thì nhân bản. */
        function takeRow($tbody) {
            var $first = $tbody.find('tr.req-row:first');

            if ($tbody.find('tr.req-row').length === 1 && !$first.find('.select-req-category').val()) {
                return $first;
            }

            var $row = $first.clone();
            var index = stdReqNextIndex($tbody);

            $row.find('option[data-legacy-analyst]').remove();
            $row.find('select, input, textarea').each(function() {
                var name = $(this).attr('name');

                if (name) $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
                $(this).val(null);
            });
            stdCleanCategoryClone($row);
            $row.find('.cell-specification, .cell-supplier, .cell-purpose').text('—');
            $tbody.append($row);

            return $row;
        }

        $modal.find('.std-kit-confirm').on('click', function() {
            if (!currentKit) return;

            var picked = $modal.find('.std-kit-check:checked').map(function() {
                return currentKit.items[+this.value];
            }).get();

            if (!picked.length) {
                alert('Vui lòng chọn ít nhất một chất chuẩn của gói.');
                return;
            }

            var $target = $(targetModalId);
            var isEdit = targetModalId.indexOf('requestEditModal') !== -1;
            var $tbody = isEdit ? $target.find('table.table-edit-req-rows tbody') : $target.find('#tableReqRows tbody');
            var criteriaClass = isEdit ? '.select2-criteria-edit' : '.select2-criteria';
            var added = [];
            var skipped = 0;

            // select2 của dòng mẫu phải gỡ trước khi nhân bản, điền xong khởi tạo lại cho cả bảng
            $tbody.find(criteriaClass + '.select2-hidden-accessible').select2('destroy');
            destroyAnalystSelect2($tbody);

            picked.forEach(function(item) {
                // Cùng chất chuẩn + cùng tên sản phẩm đã có dòng thì không thêm trùng
                var exists = $tbody.find('tr.req-row').filter(function() {
                    return String($(this).find('.select-req-category').val()) === item.category_id &&
                        $.trim($(this).find('input[name$="[product_name]"]').val()) === currentKit.product_name;
                }).length > 0;

                if (exists) {
                    skipped++;
                    return;
                }

                var $row = takeRow($tbody);
                var $criteria = $row.find(criteriaClass);

                stdSetCategory($row.find('.select-req-category'), item.category_id,
                    item.name + ' (' + item.code + ')', { code: item.code, unit: item.unit });
                if (item.unit) $row.find('.select-req-unit').val(item.unit);
                $row.find('input[name$="[requested_amount]"]').val(item.amount || '');
                $row.find('input[name$="[product_name]"]').val(currentKit.product_name);

                if (currentKit.purpose_name) {
                    if (!$criteria.find('option').filter(function() { return this.value === currentKit.purpose_name; }).length) {
                        $criteria.append(new Option(currentKit.purpose_name, currentKit.purpose_name, false, false));
                    }
                    $criteria.val([currentKit.purpose_name]);
                }

                added.push($row);
            });

            $tbody.find('.btn-remove-req-row, .btn-remove-edit-req-row').prop('disabled', $tbody.find('tr.req-row').length <= 1);

            $tbody.find(criteriaClass).each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: 'Chọn chỉ tiêu...',
                        allowClear: true,
                        dropdownParent: $target
                    });
                }
            });
            initAnalystSelect2($tbody);
            stdInitCategorySelect($tbody);

            // Nạp Quy cách / NSX / ĐVT / Mục đích của từng chất chuẩn như khi chọn tay
            added.forEach(function($row) {
                $row.find('.select-req-category').trigger('change');
            });

            $modal.modal('hide');

            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: added.length ? 'success' : 'info',
                    title: 'Đã thêm ' + added.length + ' chất chuẩn' + (skipped ? ', bỏ qua ' + skipped + ' đã có' : ''),
                    showConfirmButton: false,
                    timer: 2500
                });
            }

            var $firstEmpty = $tbody.find('input[name$="[requested_amount]"]').filter(function() {
                return !this.value;
            }).first();

            setTimeout(function() {
                $firstEmpty.trigger('focus');
            }, 300);
        });
    });
</script>
