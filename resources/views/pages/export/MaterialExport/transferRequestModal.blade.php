{{--
| Modal TẠO ĐỀ NGHỊ CHUYỂN VẬT TƯ LIÊN PHÒNG BAN (phòng A lập).
| Mỗi dòng là một vật tư cần xin; chọn phòng ban đang giữ vật tư ở đầu phiếu.
|
| Nút "Danh mục tồn của phòng" mở lại chính picker của tab đề nghị nội bộ
| (meInventoryPickerModal) để tick chọn nhiều vật tư một lần - xem data-select-class /
| data-add-row, hai khai báo này cho picker biết bảng bên đây có cấu trúc dòng khác.
--}}
<div class="modal fade md-modal" id="matTransferRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 85%; width: 85%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-people-arrows mr-1"></i> Tạo Đề Nghị Chuyển Vật Tư Liên Phòng Ban</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('pages.export.materialExport.transferRequestStore') }}" method="POST" autocomplete="off" id="formMatTransferRequest">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" name="action_type" id="matTransferActionType" value="send">

                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap: 8px;">
                        <div class="d-flex align-items-center">
                            <label class="font-weight-bold mb-0 mr-2 text-nowrap" style="font-size: 0.95rem;">
                                <i class="fas fa-building mr-1 text-primary"></i> Gửi Đến Phòng Ban <span class="text-danger">*</span>:
                            </label>
                            <div style="min-width: 280px;">
                                <select name="to_department_id" class="form-control font-weight-bold" style="height: 38px !important;" required>
                                    <option value="">-- Chọn phòng ban đang giữ vật tư --</option>
                                    @foreach ($transferDepartments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }} ({{ $dept->shortName }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <button type="button" class="btn btn-sm btn-outline-info shadow-sm btn-open-mat-stock-picker"
                                data-target-rows="#tableMatTransferRows tbody">
                                <i class="fas fa-boxes-stacked mr-1"></i> Danh mục vật tư phòng nguồn
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-add-mat-transfer-row shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Thêm vật tư
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
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
                        </style>
                        <table class="table table-bordered mb-0 mat-transfer-table" id="tableMatTransferRows" style="font-size: 0.9rem;">
                            @include('pages.export.MaterialExport.transferRowsHead')
                            <tbody data-next-idx="1">
                                @include('pages.export.MaterialExport.transferRowFields', ['idx' => 0, 'item' => null, 'onlyRow' => true])
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-3 mb-0">
                        <label class="font-weight-bold" style="font-size: 0.9rem;">Ghi chú chung</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ghi chú cho cả phiếu đề nghị...">{{ old('note') }}</textarea>
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

{{-- Mẫu một dòng vật tư, dùng chung cho cả modal tạo và các modal điều chỉnh --}}
<template id="matTransferRowTemplate">
    @include('pages.export.MaterialExport.transferRowFields', ['idx' => '__i__', 'item' => null, 'onlyRow' => false])
</template>

@if ($errors->transferCreateErrors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#matTransferRequestModal').modal('show');
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
        $tbody.append(document.getElementById('matTransferRowTemplate').innerHTML.replace(/__i__/g, next));

        // Có từ 2 dòng trở lên thì mở lại nút xoá của mọi dòng
        $tbody.find('.btn-remove-mat-transfer-row').prop('disabled', $tbody.children('tr').length <= 1);

        return $tbody.children('tr').last();
    };

    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.btn-submit-mat-transfer', function() {
            $('#matTransferActionType').val($(this).data('action') || 'send');
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
            window.matAddTransferRow('#tableMatTransferRows tbody');
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
