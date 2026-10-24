{{--
| Picker chọn PHIẾU NHẬP của phòng mình (B) để cấp phát cho 1 mục đề nghị liên phòng
| ban - mirror inventoryImportPickerModal của Chất Chuẩn, nhưng lọc theo category_id
| của đúng dòng đang cấp phát (nút mở picker nằm trong transferDetailModal, mỗi dòng
| một hoá chất khác nhau nên phải lọc lại mỗi lần mở).
|
| Người cấp được chọn BẤT KỲ mã xuất nhập còn tồn - FEFO / FIFO chỉ là badge khuyến nghị
| (cùng quy tắc App\Support\MaterialPicking): lô có hạn dùng trước, hạn gần nhất trước
| (FEFO); lô không hạn theo ngày nhập sớm nhất (FIFO). Lô "Nên xuất trước" = hạng 1 của
| hoá chất đó. Chỉ lô đã hết hạn mới khoá nút (transferIssueStore cũng chặn), lô chưa xác
| định hạn nội bộ vẫn chọn được vì chuyển NGUYÊN lô thì server cho qua.
--}}

<style>
    .ctp-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }

    .ctp-badge {
        display: inline-flex;
        align-items: center;
        padding: 1px 8px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.5;
        white-space: nowrap;
    }

    .ctp-badge.best {
        background: var(--primary);
        color: #fff;
    }

    .ctp-badge.fefo {
        background: #EDE9FE;
        color: #5B21B6;
        border-color: #DDD6FE;
    }

    .ctp-badge.fifo {
        background: #E0F2FE;
        color: #075985;
        border-color: #BAE6FD;
    }

    .ctp-badge.warn {
        background: #FEF3C7;
        color: #92400E;
        border-color: #FDE68A;
    }

    .ctp-badge.danger {
        background: #FEE2E2;
        color: #991B1B;
        border-color: #FECACA;
    }

    #chemTransferPickerTable tr.is-best td {
        background: var(--primary-soft);
    }
</style>

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
                    <table class="table table-hover table-bordered mb-0" id="chemTransferPickerTable" style="font-size: 0.88rem;" data-deferred="lazy">
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
                            @deferredRows('chemTransferPickerTable')
                            {{-- Xếp lô + hạng khuyến nghị chỉ tính ở lượt nạp dòng (pages/shared/deferredRows) --}}
                            @php
                                $ctpEps = 0.0001;
                                $ctpLots = $imports->filter(fn ($imp) => (float) $imp->remaining > $ctpEps)
                                    ->sort(function ($a, $b) {
                                        $key = fn ($imp) => [
                                            $imp->expired ? 1 : 0,                 // lô hết hạn xuống cuối
                                            $imp->expired_date ? 0 : 1,            // 1. lô có hạn trước lô không hạn
                                            (string) $imp->expired_date,           // 2. FEFO
                                            (string) $imp->imported_date,          // 3. FIFO
                                            (int) $imp->id,
                                        ];

                                        return $key($a) <=> $key($b);
                                    })
                                    ->values();

                                // Hạng khuyến nghị trong từng hoá chất (chỉ tính lô còn hạn)
                                $ctpRank = [];
                                $ctpCounter = [];
                                foreach ($ctpLots as $lot) {
                                    if (! $lot->expired) {
                                        $ctpCounter[$lot->category_id] = ($ctpCounter[$lot->category_id] ?? 0) + 1;
                                        $ctpRank[$lot->id] = $ctpCounter[$lot->category_id];
                                    }
                                }

                                $ctpDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
                            @endphp
                            @forelse ($ctpLots as $imp)
                                @php
                                    $rank = $ctpRank[$imp->id] ?? null;
                                    $rule = $imp->expired_date ? 'FEFO' : 'FIFO';
                                    $ruleText = $rule === 'FEFO'
                                        ? 'FEFO · hạn '.$ctpDate($imp->expired_date)
                                        : 'FIFO · nhập '.($ctpDate($imp->imported_date) ?: '—');
                                @endphp
                                <tr class="chem-transfer-import-row {{ $rank === 1 ? 'is-best' : '' }}" data-category-id="{{ $imp->category_id }}">
                                    <td class="text-center">{{ $rank ?: '—' }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $imp->code }}</div>
                                        <div class="ctp-badges">
                                            @if ($rank === 1)
                                                <span class="ctp-badge best"><i class="fas fa-star mr-1"></i>Nên xuất trước</span>
                                            @endif
                                            <span class="ctp-badge {{ strtolower($rule) }}">{{ $ruleText }}</span>
                                            @if ($imp->expired)
                                                <span class="ctp-badge danger"><i class="fas fa-ban mr-1"></i>Hết hạn</span>
                                            @elseif ($imp->waiting_internal)
                                                <span class="ctp-badge warn" title="Chưa xác định hạn dùng nội bộ - chỉ cấp phát được nguyên cả lô">
                                                    <i class="fas fa-box mr-1"></i>Chỉ chuyển nguyên lô
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $imp->chem_name }} <br><small class="text-muted">{{ $imp->category_code }}</small></td>
                                    <td>{{ $imp->batch_no ?: '—' }}</td>
                                    <td class="text-right font-weight-bold text-success">{{ $expNum($imp->remaining) }} {{ $imp->unit_short_name }}</td>
                                    <td>
                                        @if ($imp->expired_date)
                                            <span class="{{ $imp->expired ? 'text-danger font-weight-bold' : '' }}">{{ $ctpDate($imp->expired_date) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary btn-select-chem-transfer-import"
                                            {{ $imp->expired ? 'disabled' : '' }}
                                            data-import-id="{{ $imp->id }}"
                                            data-import-code="{{ $imp->code }}"
                                            data-remaining="{{ (float) $imp->remaining }}"
                                            data-unit="{{ $imp->unit_short_name }}"
                                            data-rule="{{ $rule }}"
                                            data-rule-text="{{ $ruleText }}"
                                            data-best="{{ $rank === 1 ? 1 : 0 }}">
                                            Chọn
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Không có phiếu nhập nào trong kho.</td>
                                </tr>
                            @endforelse
                            @enddeferredRows
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

        $('#searchChemTransferImport').val('');
        $('#chemTransferPickerModal').modal('show');

        // Danh sách lô (~1.400 dòng) chỉ lấy ở lần mở đầu - pages/shared/deferredRows
        mdDeferred.fillLazy('#chemTransferPickerTable', function() {
            $('#chemTransferPickerTable tbody tr.chem-transfer-import-row').each(function() {
                $(this).toggle(String($(this).data('category-id')) === categoryId);
            });
        }, 'Đang tải danh sách lô hoá chất...');
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
        var esc = function(text) { return $('<div>').text(text == null ? '' : text).html(); };
        var importCode = $btn.data('import-code');
        var remaining = $btn.data('remaining');
        var unit = $btn.data('unit') || '';
        var rule = String($btn.data('rule') || '').toLowerCase();

        // Giữ badge FEFO / FIFO cạnh mã đã chọn để người cấp biết mình có đi đúng khuyến nghị không
        $chemPickerTargetRow.find('.chem-pick-display')
            .removeClass('is-empty')
            .attr('data-import-id', $btn.data('import-id'))
            .html('<span class="font-weight-bold">' + esc(importCode) + '</span>' +
                '<span class="text-muted ml-1">còn ' + esc(remaining) + ' ' + esc(unit) + '</span>' +
                '<div class="ctp-badges">' +
                    ($btn.data('best') == 1 ? '<span class="ctp-badge best"><i class="fas fa-star mr-1"></i>Nên xuất trước</span>' : '') +
                    '<span class="ctp-badge ' + esc(rule) + '">' + esc($btn.data('rule-text')) + '</span>' +
                '</div>');

        $chemPickerTargetRow.find('.input-issue-amount').attr('max', remaining);

        $('#chemTransferPickerModal').modal('hide');
    });
});
</script>
