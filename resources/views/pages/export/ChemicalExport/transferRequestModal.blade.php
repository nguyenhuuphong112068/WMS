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

                    <div class="d-flex flex-wrap mb-3" style="gap: 12px;">
                        <div class="form-group mb-0 flex-grow-1" style="min-width: 280px;">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">
                                Tiêu Đề Đề Nghị <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control font-weight-bold" maxlength="255"
                                list="chemTitleOptionsTransfer" autocomplete="off"
                                placeholder="VD: Xin hoá chất phục vụ thí nghiệm..." value="{{ old('title') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">Ngày Mong Muốn</label>
                            <input type="date" name="needed_date" class="form-control" value="{{ old('needed_date') }}">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
                        <div class="d-flex align-items-center">
                            <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                            </label>
                            <div style="min-width: 280px;">
                                <select name="to_department_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                    <option value="">-- Chọn phòng ban đang giữ hoá chất --</option>
                                    @include('layout.departmentOptions', ['departments' => $transferDepartments, 'selected' => old('to_department_id')])
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-chem-stock-picker"
                                data-target-rows="#tableChemTransferRows tbody">
                                <i class="fas fa-flask mr-1"></i> Danh mục hoá chất phòng nguồn
                            </button>
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
                                    <th style="width: 110px">Mã Hoá Chất</th>
                                    <th style="min-width: 260px">Hoá Chất <span class="text-danger">*</span></th>
                                    <th style="min-width: 130px">Số Lượng ĐN <span class="text-danger">*</span></th>
                                    <th style="min-width: 100px">ĐVT</th>
                                    <th style="min-width: 220px">Ghi Chú</th>
                                    <th style="min-width: 45px" class="text-center">#</th>
                                </tr>
                            </thead>
                            <tbody data-next-idx="1">
                                <tr class="chem-transfer-row">
                                    <td class="text-center">
                                        <span class="badge badge-secondary px-2 py-1 chem-transfer-code" style="font-size: 0.82rem;"></span>
                                    </td>
                                    <td>
                                        {{-- Gõ tìm qua AJAX (mọi hoá chất đã duyệt, kèm đơn vị phòng mình) --}}
                                        <select name="items[0][category_id]" class="form-control select-chem-transfer-category"
                                            data-lookup-url="{{ route('pages.category.lookup.chemicalCategories', ['scope' => 'approved']) }}" required>
                                            <option value="">-- Chọn hoá chất --</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" inputmode="decimal" min="0.0001" name="items[0][requested_amount]" class="form-control text-right js-decimal" placeholder="0.0000" required>
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
    /**
     * Thêm một dòng hoá chất vào bảng đề nghị liên phòng ban và trả về dòng vừa thêm.
     * Chỉ số items[i] đếm riêng từng bảng ở data-next-idx nên form tạo và các form điều
     * chỉnh không đụng nhau. Picker "Danh mục hoá chất phòng nguồn" cũng gọi hàm này.
     */
    /*
    | Ô chọn hoá chất của dòng đề nghị: select2 tìm qua AJAX (data-lookup-url), không in sẵn
    | ~1.000 option cho mỗi dòng / mỗi modal điều chỉnh.
    */
    window.chemInitTransferSelect = function ($select) {
        if (!$select.length || $select.hasClass('select2-hidden-accessible')) return;

        var $modal = $select.closest('.modal');

        $select.select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $modal.length ? $modal : $(document.body),
            placeholder: '-- Chọn hoá chất --',
            ajax: wmsLookupAjax($select),
            language: {
                noResults: function () { return 'Không tìm thấy dữ liệu phù hợp'; },
                searching: function () { return 'Đang tìm...'; },
                loadingMore: function () { return 'Đang tải thêm...'; },
                errorLoading: function () { return 'Không tải được dữ liệu, vui lòng thử lại'; }
            }
        });
    };

    window.chemAddTransferRow = function (tbody) {
        var $tbody = $(tbody);
        var next = parseInt($tbody.attr('data-next-idx') || $tbody.children('tr').length, 10);
        var $newRow = $tbody.children('tr').first().clone();

        // Bản sao mang theo khung select2 của dòng gốc: gỡ ra rồi khởi tạo lại cho ô mới
        $newRow.find('.select2-container').remove();
        $newRow.find('select.select-chem-transfer-category')
            .removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id aria-hidden tabindex')
            .find('option').not('[value=""]').remove();

        $newRow.find('select, input, textarea').each(function () {
            var name = $(this).attr('name');

            if (name) {
                $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + next + ']'));
            }

            $(this).val(null);
        });

        $newRow.find('.chem-transfer-code').text('');
        $tbody.attr('data-next-idx', next + 1);
        $tbody.append($newRow);
        window.chemInitTransferSelect($newRow.find('select.select-chem-transfer-category'));
        $tbody.find('.btn-remove-chem-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);

        return $tbody.children('tr').last();
    };

    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-chem-transfer-action', function() {
            var action = $(this).data('action') || 'send';
            $('#chemTransferRequestActionType').val(action);
        });

        $(document).on('input', '#tableChemTransferRows .auto-resize', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        $('.select-chem-transfer-category').each(function() {
            window.chemInitTransferSelect($(this));
        });

        function chemApplyTransferUnit($row, unit) {
            if (unit && $row.find('select[name*="[requested_unit]"] option[value="' + unit + '"]').length) {
                $row.find('select[name*="[requested_unit]"]').val(unit);
            }
        }

        // Chọn hoá chất thì hiện mã và điền sẵn đơn vị phòng mình đang dùng cho hoá chất đó.
        // Kết quả tìm AJAX mang sẵn code/unit; option render sẵn mang data-code/data-unit.
        $(document).on('change', '.select-chem-transfer-category', function() {
            var $select = $(this);
            var $row = $select.closest('tr');
            var item = wmsSelectedItems($select)[0] || {};

            $row.find('.chem-transfer-code').text(item.code || '');

            if (item.unit !== undefined) {
                chemApplyTransferUnit($row, item.unit);
                return;
            }

            // Option dựng từ picker tồn phòng nguồn chưa biết đơn vị phòng mình: tra lại theo mã
            if (!item.id || !item.code) return;

            $.getJSON($select.data('lookup-url'), { q: item.code, page: 1 }).done(function(res) {
                var hit = ((res && res.results) || []).filter(function(r) { return String(r.id) === String(item.id); })[0];

                if (hit && String($select.val()) === String(item.id)) {
                    $select.find('option:selected').attr('data-unit', hit.unit || '');
                    chemApplyTransferUnit($row, hit.unit);
                }
            });
        });

        $(document).on('click', '.btn-add-chem-transfer-row', function() {
            window.chemAddTransferRow('#tableChemTransferRows tbody');
        });

        $(document).on('click', '.btn-remove-chem-transfer-row', function() {
            var $tbody = $(this).closest('tbody');

            if ($tbody.children('tr').length > 1) {
                $(this).closest('tr').remove();
                $tbody.find('.btn-remove-chem-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);
            }
        });
    });
</script>
