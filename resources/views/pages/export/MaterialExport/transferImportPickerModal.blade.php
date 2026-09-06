{{--
| Picker chọn MÃ XUẤT NHẬP của phòng mình (B) để cấp phát cho một mục đề nghị liên phòng
| ban. Bảng lô lấy từ $availableImports (đã sắp theo thứ tự nên xuất - FEFO rồi FIFO),
| lọc lại theo đúng vật tư của dòng đang cấp phát mỗi lần mở.
--}}
<div class="modal fade" id="matTransferPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes mr-2"></i> Chọn Mã Xuất Nhập Trong Kho Để Cấp Phát
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
                            <input type="text" id="searchMatTransferImport" class="form-control" placeholder="Tìm mã xuất nhập, tên vật tư, định khu...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded shadow-sm border" style="max-height: 60vh;">
                    <table class="table table-hover table-bordered mb-0" id="matTransferPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 50px">STT</th>
                                <th>Mã Xuất Nhập</th>
                                <th>Tên Vật Tư</th>
                                <th>Định Khu</th>
                                <th>Còn Hứa Được</th>
                                <th>Hạn Dùng</th>
                                <th style="width: 90px">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $stt = 1; @endphp
                            @forelse ($availableImports as $imp)
                                <tr class="mat-transfer-import-row" data-category-id="{{ $imp->category_id }}">
                                    <td class="text-center">{{ $stt++ }}</td>
                                    <td class="font-weight-bold">{{ $imp->code }}</td>
                                    <td>
                                        {{ $imp->material_name }}
                                        @if ($imp->technical_specification)
                                            <br><small class="text-muted">{{ $imp->technical_specification }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $imp->location_code ?: '—' }}</td>
                                    <td class="text-right font-weight-bold text-success">
                                        {{ $expNum($imp->available) }} {{ $imp->unit_short_name }}
                                    </td>
                                    <td class="text-center">
                                        @if ($imp->expired_date)
                                            <span class="{{ $imp->expired ? 'text-danger font-weight-bold' : '' }}">{{ $expDate($imp->expired_date) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-mat-transfer-import"
                                            {{ ! $imp->selectable ? 'disabled' : '' }}
                                            data-import-id="{{ $imp->id }}"
                                            data-import-code="{{ $imp->code }}"
                                            data-available="{{ (float) $imp->available }}"
                                            data-unit="{{ $imp->unit_short_name }}">
                                            Chọn
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Không có mã xuất nhập nào trong kho.</td>
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
        var $matPickerTargetRow = null;

        // Mở picker từ nút trong transferDetailModal - nhớ dòng đang cấp phát và lọc đúng
        // vật tư của dòng đó (mỗi dòng đề nghị là một vật tư khác nhau).
        $(document).on('click', '.btn-open-mat-picker', function() {
            $matPickerTargetRow = $(this).closest('.mat-issue-row');
            var categoryId = String($(this).data('category-id'));

            $('#matTransferPickerTable tbody tr.mat-transfer-import-row').each(function() {
                $(this).toggle(String($(this).data('category-id')) === categoryId);
            });

            $('#searchMatTransferImport').val('');
            $('#matTransferPickerModal').modal('show');
        });

        $('#searchMatTransferImport').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            var activeCategory = $matPickerTargetRow
                ? String($matPickerTargetRow.find('.btn-open-mat-picker').data('category-id'))
                : null;

            $('#matTransferPickerTable tbody tr.mat-transfer-import-row').each(function() {
                var matchesCategory = !activeCategory || String($(this).data('category-id')) === activeCategory;
                var matchesText = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(matchesCategory && matchesText);
            });
        });

        $(document).on('click', '.btn-select-mat-transfer-import', function() {
            if (!$matPickerTargetRow) {
                return;
            }

            var $btn = $(this);
            var available = $btn.data('available');
            var unit = $btn.data('unit') || '';

            $matPickerTargetRow.find('.mat-pick-display')
                .removeClass('is-empty')
                .attr('data-import-id', $btn.data('import-id'))
                .html('<span class="font-weight-bold">' + $btn.data('import-code') + '</span>' +
                    '<span class="text-muted ml-1">còn ' + available + ' ' + unit + '</span>');

            $matPickerTargetRow.find('.input-mat-issue-amount').attr('max', available);

            $('#matTransferPickerModal').modal('hide');
        });
    });
</script>
