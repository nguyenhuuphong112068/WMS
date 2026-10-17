{{--
| Modal TẠO ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN (phòng A lập).
| Mỗi dòng là một vật tư cần xin; chọn phòng ban đang giữ vật tư ở đầu phiếu.
|
| Nút "Danh mục vật tư phòng nguồn" mở picker tồn kho của phòng nhận đề nghị
| (transferStockPickerModal) để tick chọn nhiều vật tư một lần.
|
| Dựng HAI modal cùng một form, chỉ khác phòng nhận:
|   #matTransferRequestModal : Chuyển liên phòng ban - tự chọn phòng (đã loại phòng Hành Chính).
|   #matVppRequestModal      : Đề nghị VPP - phòng nhận cố định là phòng Hành Chính ($vppTarget).
| Lưu lỗi validate thì chỉ modal của đúng loại vừa gửi được điền lại old() và tự mở lại.
--}}
@php
    $mtCreateModals = [[
        'kind' => 'transfer',
        'id' => 'matTransferRequestModal',
        'table' => 'tableMatTransferRows',
        'icon' => 'fas fa-people-arrows',
        'title' => 'Tạo Đề Nghị Chuyển Vật Tư Liên Phòng Ban',
        'placeholder' => 'VD: Xin vật tư bảo trì máy đóng gói...',
        'departments' => $transferDepartments,
        'categories' => $transferCategories,
        'template' => 'matTransferRowTemplate',
    ]];

    if (! empty($vppTarget)) {
        $mtCreateModals[] = [
            'kind' => \App\Support\MaterialVpp::TAB,
            'id' => 'matVppRequestModal',
            'table' => 'tableMatVppRows',
            'icon' => 'fas fa-paperclip',
            'title' => 'Tạo Đề Nghị Vật Tư Từ Kho VPP',
            'placeholder' => 'VD: Đề nghị văn phòng phẩm tháng ' . now()->format('m/Y'),
            'departments' => collect([$vppTarget]),
            // Chỉ vật tư Hành Chánh mua (App\Support\MaterialVpp)
            'categories' => $vppCategories,
            'template' => 'matVppRowTemplate',
        ];
    }

    $mtHasCreateErrors = $errors->transferCreateErrors->any();
    $mtOldKind = $mtHasCreateErrors ? \App\Support\MaterialVpp::tabFor((int) old('to_department_id')) : null;
@endphp

@foreach ($mtCreateModals as $mtCm)
@php
    $mtIsVpp = $mtCm['kind'] !== 'transfer';
    $mtOld = fn ($key) => $mtOldKind === $mtCm['kind'] ? old($key) : null;
@endphp
<div class="modal fade md-modal" id="{{ $mtCm['id'] }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $mtCm['icon'] }} mr-1"></i> {{ $mtCm['title'] }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.materialExport.transferRequestStore') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" name="action_type" class="mat-transfer-action-type" value="send">

                    <div class="d-flex flex-wrap mb-3" style="gap: 12px;">
                        <div class="form-group mb-0 flex-grow-1" style="min-width: 280px;">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">
                                Tiêu Đề Đề Nghị <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control font-weight-bold" maxlength="255"
                                placeholder="{{ $mtCm['placeholder'] }}" value="{{ $mtOld('title') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold" style="font-size: 0.9rem;">Ngày Mong Muốn</label>
                            <input type="date" name="needed_date" class="form-control" value="{{ $mtOld('needed_date') }}">
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap: 8px;">
                        <div class="d-flex align-items-center">
                            <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                            </label>
                            <div style="min-width: 280px;">
                                {{-- Đề nghị VPP: chỉ một phòng Hành Chính, khoá luôn ô chọn (vẫn là select để picker tồn kho đọc được) --}}
                                <select name="to_department_id" class="form-control font-weight-bold {{ $mtIsVpp ? 'mat-transfer-dept-fixed' : '' }}"
                                    style="height: 38px !important;" {{ $mtIsVpp ? 'tabindex=-1' : '' }} required>
                                    @unless ($mtIsVpp)
                                        <option value="">-- Chọn phòng ban đang giữ vật tư --</option>
                                    @endunless
                                    @foreach ($mtCm['departments'] as $dept)
                                        <option value="{{ $dept->id }}" {{ $mtIsVpp || $mtOld('to_department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->shortName }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-mat-stock-picker"
                                data-target-rows="#{{ $mtCm['table'] }} tbody">
                                <i class="fas fa-boxes-stacked mr-1"></i> {{ $mtIsVpp ? 'Danh mục kho VPP' : 'Danh mục vật tư phòng nguồn' }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-mat-transfer-row shadow-sm"
                                data-rows="#{{ $mtCm['table'] }} tbody">
                                <i class="fas fa-plus mr-1"></i> Thêm vật tư
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        @if ($loop->first)
                        <style>
                            .mat-transfer-table td { vertical-align: middle !important; padding: 8px !important; }
                            .mat-transfer-table th {
                                white-space: nowrap; vertical-align: middle; background-color: #f1f5f9;
                                font-size: 0.88rem; padding: 10px 8px !important;
                            }
                            .mat-transfer-table .form-control {
                                min-height: 38px !important; height: 38px !important; font-size: 0.9rem !important;
                                line-height: 1.5 !important; padding: 6px 10px !important;
                            }
                            .mat-transfer-table textarea.auto-resize {
                                resize: vertical; min-height: 38px !important; height: 38px !important;
                                line-height: 1.4 !important; overflow-y: auto;
                            }
                            .mat-transfer-code:empty::before { content: '—'; color: #94a3b8; }
                            .mat-transfer-dept-fixed { pointer-events: none; background: var(--primary-soft) !important; }
                        </style>
                        @endif
                        <table class="table table-bordered mb-0 mat-transfer-table" id="{{ $mtCm['table'] }}" style="font-size: 0.9rem;">
                            @include('pages.export.MaterialExport.transferRowsHead')
                            <tbody data-next-idx="1" data-template="{{ $mtCm['template'] }}">
                                @include('pages.export.MaterialExport.transferRowFields', ['idx' => 0, 'item' => null, 'onlyRow' => true, 'rowCategories' => $mtCm['categories']])
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-3 mb-0">
                        <label class="font-weight-bold" style="font-size: 0.9rem;">Ghi chú chung</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú cho cả phiếu đề nghị...">{{ $mtOld('note') }}</textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-outline-primary btn-submit-mat-transfer" data-action="draft"><i class="fas fa-save mr-1"></i> Lưu tạm</button>
                    <button type="submit" class="btn btn-primary btn-submit-mat-transfer" data-action="send"><i class="fas fa-paper-plane mr-1"></i> Gửi đề nghị</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Mẫu một dòng vật tư, dùng chung cho cả modal tạo và các modal điều chỉnh; đề nghị VPP có mẫu riêng chỉ gồm vật tư Hành Chánh mua --}}
<template id="matTransferRowTemplate">
    @include('pages.export.MaterialExport.transferRowFields', ['idx' => '__i__', 'item' => null, 'onlyRow' => false])
</template>
<template id="matVppRowTemplate">
    @include('pages.export.MaterialExport.transferRowFields', ['idx' => '__i__', 'item' => null, 'onlyRow' => false, 'rowCategories' => $vppCategories])
</template>

@if ($mtHasCreateErrors)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $(@json($mtOldKind === 'transfer' ? '#matTransferRequestModal' : '#matVppRequestModal')).modal('show');
        });
    </script>
@endif

<script>
    /**
     * Thêm một dòng vật tư vào bảng đề nghị liên phòng ban và trả về dòng vừa thêm.
     * Chỉ số items[i] đếm riêng từng bảng ở data-next-idx nên các modal không đụng nhau.
     * Modal "Danh mục tồn của phòng" cũng gọi hàm này (xem data-add-row của nút mở).
     */
    window.matAddTransferRow = function (tbody) {
        var $tbody = $(tbody);
        var next = parseInt($tbody.attr('data-next-idx') || $tbody.children('tr').length, 10);

        $tbody.attr('data-next-idx', next + 1);
        $tbody.append(document.getElementById($tbody.attr('data-template') || 'matTransferRowTemplate').innerHTML.replace(/__i__/g, next));

        // Có từ 2 dòng trở lên thì mở lại nút xoá của mọi dòng
        $tbody.find('.btn-remove-mat-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);

        return $tbody.children('tr').last();
    };

    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-mat-transfer', function() {
            $(this).closest('form').find('.mat-transfer-action-type').val($(this).data('action') || 'send');
        });

        $(document).on('input', '.mat-transfer-table .auto-resize', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Chọn vật tư thì hiện mã và điền sẵn đơn vị phòng mình đang dùng cho vật tư đó
        $(document).on('change', '.select-mat-transfer-category', function() {
            var $option = $(this).find('option:selected');
            var $row = $(this).closest('tr');
            var unit = $option.data('unit') || '';

            $row.find('.mat-transfer-code').text($option.data('code') || '');

            if (unit && $row.find('select[name*="[requested_unit]"] option[value="' + unit + '"]').length) {
                $row.find('select[name*="[requested_unit]"]').val(unit);
            }
        });

        $(document).on('click', '.btn-add-mat-transfer-row', function() {
            window.matAddTransferRow($(this).data('rows') || '#tableMatTransferRows tbody');
        });

        $(document).on('click', '.btn-remove-mat-transfer-row', function() {
            var $tbody = $(this).closest('tbody');

            if ($tbody.children('tr').length > 1) {
                $(this).closest('tr').remove();
                $tbody.find('.btn-remove-mat-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);
            }
        });
    });
</script>
