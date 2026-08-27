{{-- Modal chọn ống chuẩn từ tồn kho chung (dùng cho Loại phiếu = Loại bỏ) --}}
<div class="modal fade" id="inventoryImportPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 90vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white py-2">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.05rem;">
                    <i class="fas fa-boxes mr-2"></i> Chọn Ống Chuẩn Tồn Kho (Để Loại Bỏ)
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
                            <input type="text" id="searchInventoryImport" class="form-control" placeholder="Tìm mã ống, chất chuẩn, lô...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive bg-white rounded shadow-sm border" style="max-height: 60vh;">
                    <table class="table table-hover table-bordered mb-0" id="inventoryImportPickerTable" style="font-size: 0.88rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 50px">STT</th>
                                <th>Mã Ống Chuẩn</th>
                                <th>Tên Chất Chuẩn</th>
                                <th>Lô</th>
                                <th>Tồn Kho</th>
                                <th>Hạn Dùng</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $stt = 1; @endphp
                            @forelse ($availableImports as $imp)
                            <tr class="inv-import-row">
                                <td class="text-center">{{ $stt++ }}</td>
                                <td class="font-weight-bold">{{ $imp->code }}</td>
                                <td>{{ $imp->standard_name }} <br><small class="text-muted">v{{ $imp->category_version }}</small></td>
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
                                    <button type="button" class="btn btn-sm btn-danger btn-select-inv-import" 
                                        data-import-id="{{ $imp->id }}"
                                        data-import-code="{{ $imp->code }}"
                                        data-std-name="{{ $imp->standard_name }}"
                                        data-remaining="{{ (float)$imp->remaining }}"
                                        data-unit="{{ $imp->unit_short_name }}"
                                        data-category-code="{{ $imp->category_code }}"
                                        data-supplier="{{ $imp->supplier_id }}" 
                                        data-spec="{{ $imp->specification ?? '' }}"
                                        data-batch="{{ $imp->batch_no }}"
                                        data-potency="{{ $imp->potency ?? '' }}"
                                        data-moisture="{{ $imp->moisture ?? '' }}"
                                        data-other="{{ $imp->standard_form ?? '' }}"
                                        data-attachments="{{ json_encode($imp->attachments ?? []) }}"
                                        data-expiry-type="{{ $imp->expiry_type ?? '' }}"
                                        data-return-standard="0"
                                        data-expired="{{ $imp->expired_date ? \Carbon\Carbon::parse($imp->expired_date)->format('d/m/Y') : '' }}">
                                        Chọn
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Không có ống chuẩn nào trong kho.</td>
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
    $('#searchInventoryImport').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#inventoryImportPickerTable tbody tr.inv-import-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $(document).on('click', '.btn-select-inv-import', function() {
        let $btn = $(this);
        let attachments = $btn.data('attachments') || [];
        if (typeof attachments === 'string') {
            try { attachments = JSON.parse(attachments); } catch(e) { attachments = []; }
        }

        if (typeof window.populateStandardDisplay === 'function') {
            window.populateStandardDisplay({
                import_id: $btn.data('import-id'),
                import_code: $btn.data('import-code'),
                std_name: $btn.data('std-name'),
                remaining: $btn.data('remaining'),
                unit: $btn.data('unit'),
                category_code: $btn.data('category-code') || '',
                supplier: $btn.data('supplier') || '—',
                spec: $btn.data('spec') || '—',
                batch: $btn.data('batch') || '—',
                potency: $btn.data('potency'),
                moisture: $btn.data('moisture'),
                other: $btn.data('other'),
                attachments: attachments,
                expiry_type: $btn.data('expiry-type') || '',
                expired: $btn.data('expired'),
                return_standard: 0
            });
        }

        $('#inventoryImportPickerModal').modal('hide');
    });
});
</script>
