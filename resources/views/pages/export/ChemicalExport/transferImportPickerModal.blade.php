{{--
| Picker chọn PHIẾU NHẬP của phòng mình (B) để cấp phát cho 1 mục đề nghị liên phòng
| ban - mirror inventoryImportPickerModal của Chất Chuẩn, nhưng lọc theo category_id
| của đúng dòng đang cấp phát (nút mở picker nằm trong transferDetailModal, mỗi dòng
| một hoá chất khác nhau nên phải lọc lại mỗi lần mở).
--}}
<div class="modal fade" id="chemTransferPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes mr-2"></i> Chọn Phiếu Nhập Trong Kho Để Cấp Phát
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3 bg-light">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="searchChemTransferImport" class="form-control" placeholder="Tìm mã xuất nhập, hoá chất, lô...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded shadow-sm border" style="max-height: 60vh;">
                    <table class="table table-hover table-bordered mb-0" id="chemTransferPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 50px">STT</th>
                                <th>Mã Xuất Nhập</th>
                                <th>Tên Hoá Chất</th>
                                <th>Lô</th>
                                <th>Tồn Kho</th>
                                <th>Hạn Dùng</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $stt = 1; @endphp
                            @forelse ($imports as $imp)
                                <tr class="chem-transfer-import-row" data-category-id="{{ $imp->category_id }}">
                                    <td class="text-center">{{ $stt++ }}</td>
                                    <td class="font-weight-bold">{{ $imp->code }}</td>
                                    <td>{{ $imp->chem_name }} <br><small class="text-muted">{{ $imp->category_code }}</small></td>
                                    <td>{{ $imp->batch_no ?: '—' }}</td>
                                    <td class="text-right font-weight-bold text-success">{{ $expNum($imp->remaining) }} {{ $imp->unit_short_name }}</td>
                                    <td>
                                        @if ($imp->expired_date)
                                            @if ($imp->expired)
                                                <span class="text-danger font-weight-bold">{{ \Carbon\Carbon::parse($imp->expired_date)->format('d/m/Y') }}</span>
                                            @else
                                                {{ \Carbon\Carbon::parse($imp->expired_date)->format('d/m/Y') }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-chem-transfer-import"
                                            {{ ! $imp->selectable ? 'disabled' : '' }}
                                            data-import-id="{{ $imp->id }}"
                                            data-import-code="{{ $imp->code }}"
                                            data-remaining="{{ (float) $imp->remaining }}"
                                            data-unit="{{ $imp->unit_short_name }}">
                                            Chọn
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Không có phiếu nhập nào trong kho.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var $chemPickerTargetRow = null;

    // Mở picker từ nút trong transferDetailModal - nhớ dòng đang cấp phát và lọc
    // đúng hoá chất của dòng đó (mỗi dòng đề nghị là một hoá chất khác nhau).
    $(document).on('click', '.btn-open-chem-picker', function() {
        $chemPickerTargetRow = $(this).closest('.issue-row');
        var categoryId = String($(this).data('category-id'));

        $('#chemTransferPickerTable tbody tr.chem-transfer-import-row').each(function() {
            $(this).toggle(String($(this).data('category-id')) === categoryId);
        });

        $('#searchChemTransferImport').val('');
        $('#chemTransferPickerModal').modal('show');
    });

    $('#searchChemTransferImport').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var activeCategory = $chemPickerTargetRow
            ? String($chemPickerTargetRow.find('.btn-open-chem-picker').data('category-id'))
            : null;

        $('#chemTransferPickerTable tbody tr.chem-transfer-import-row').filter(function() {
            var matchesCategory = ! activeCategory || String($(this).data('category-id')) === activeCategory;
            var matchesText = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(matchesCategory && matchesText);
        });
    });

    $(document).on('click', '.btn-select-chem-transfer-import', function() {
        if (! $chemPickerTargetRow) {
            return;
        }

        var $btn = $(this);
        var importCode = $btn.data('import-code');
        var remaining = $btn.data('remaining');
        var unit = $btn.data('unit') || '';

        $chemPickerTargetRow.find('.chem-pick-display')
            .removeClass('is-empty')
            .attr('data-import-id', $btn.data('import-id'))
            .html('<span class="font-weight-bold">' + importCode + '</span>' +
                '<span class="text-muted ml-1">còn ' + remaining + ' ' + unit + '</span>');

        $chemPickerTargetRow.find('.input-issue-amount').attr('max', remaining);

        $('#chemTransferPickerModal').modal('hide');
    });
});
</script>
