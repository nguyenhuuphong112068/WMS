{{--
| TỒN KHO HOÁ CHẤT - MODAL CÁCH LY (bước 1 của quy trình hoá chất hỏng, ChemicalQuarantineController)
|
| Mở từ nút [Cách ly] trên từng mã xuất nhập ở tab "Theo mã xuất nhập" / "Cận hạn dùng" (script.blade.php,
| class .btn-inv-quarantine, data-row JSON). Lưu xong hàng đã trừ tồn; quyết định loại bỏ / trả về kho
| và huỷ làm ở Sử Dụng Hoá Chất → tab "Cách ly - Loại bỏ - Huỷ".
--}}

@php
    $iqBag = $errors->getBag('quarantineErrors');
    $iqLocations = collect($quarantinePendingLocations ?? []);
    $iqLocationText = fn($loc) => $loc->code . ' — ' . implode(' / ', array_filter([$loc->warehouse_name, $loc->shelf_name, $loc->column_name, $loc->tier_name]));
@endphp

<style>
    .iq-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        border-radius: var(--border-radius-md, 8px);
        background: var(--primary-soft);
    }

    .iq-summary small {
        display: block;
        color: var(--primary-dark);
        opacity: 0.8;
    }
</style>

@perm('export_chemical_quarantine')
<div class="modal fade md-modal" id="invQuarantineModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-biohazard mr-2"></i>Cách ly hoá chất chờ quyết định</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('pages.export.chemicalQuarantine.store') }}" method="POST" id="invQuarantineForm">
                @csrf
                <input type="hidden" name="return_tab" value="{{ old('return_tab', 'code') }}">
                <input type="hidden" name="items[0][import_id]" value="{{ old('items.0.import_id') }}">
                {{-- Chỉ để dựng lại phần tóm tắt khi form mở lại kèm lỗi, server không đọc --}}
                <input type="hidden" name="lot_info" value="{{ old('lot_info') }}">

                <div class="modal-body">
                    @if ($iqBag->has('items') || $iqBag->has('items.0.import_id'))
                        <div class="alert alert-danger py-2">{{ $iqBag->first('items') ?: $iqBag->first('items.0.import_id') }}</div>
                    @endif

                    <div class="iq-summary">
                        <div><small>Mã xuất nhập</small><b class="exp-code iq-code">—</b></div>
                        <div><small>Hoá chất</small><b class="iq-chem">—</b></div>
                        <div><small>Vị trí hiện tại</small><b class="iq-location">—</b></div>
                        <div><small>Hạn áp dụng</small><b class="iq-expiry">—</b></div>
                        <div><small>Tồn hiện tại</small><b class="iq-limit">—</b></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label>Số lượng cách ly <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" inputmode="decimal" name="items[0][amount]" required
                                    class="form-control js-decimal {{ $iqBag->has('items.0.amount') ? 'is-invalid' : '' }}"
                                    value="{{ old('items.0.amount') }}">
                                <div class="input-group-append"><span class="input-group-text iq-unit">—</span></div>
                            </div>
                            @if ($iqBag->has('items.0.amount')) <div class="md-error text-danger small">{{ $iqBag->first('items.0.amount') }}</div> @endif
                        </div>
                        <div class="form-group col-md-7">
                            <label>Định khu Chờ Quyết Định <span class="text-danger">*</span></label>
                            <select name="pending_location_id" required {{ $iqLocations->isEmpty() ? 'disabled' : '' }}
                                class="form-control wms-zone-select {{ $iqBag->has('pending_location_id') ? 'is-invalid' : '' }}">
                                <option value="">-- Chọn vị trí đặt hoá chất cách ly --</option>
                                @foreach ($iqLocations as $loc)
                                    <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type }}"
                                        {{ (string) old('pending_location_id', $iqLocations->count() === 1 ? $loc->id : '') === (string) $loc->id ? 'selected' : '' }}>
                                        {{ $iqLocationText($loc) }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($iqLocations->isEmpty())
                                <div class="md-error text-danger small">Phòng ban chưa khai định khu hoá chất loại "Chờ Quyết Định" ở Dữ Liệu Gốc → Định Khu.</div>
                            @endif
                            @if ($iqBag->has('pending_location_id')) <div class="md-error text-danger small">{{ $iqBag->first('pending_location_id') }}</div> @endif
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Lý do cách ly <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" required
                            class="form-control {{ $iqBag->has('reason') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Hết hạn sử dụng, đổi màu, nhiễm bẩn, kết quả OOS...">{{ old('reason') }}</textarea>
                        @if ($iqBag->has('reason')) <div class="md-error text-danger small">{{ $iqBag->first('reason') }}</div> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger" {{ $iqLocations->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-biohazard mr-1"></i> Cách ly
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $m = $('#invQuarantineModal');
        var IQ_EPS = 0.00005;
        var iqLimit = null;

        function iqNum(value) {
            var n = Number(value) || 0;
            var parts = n.toFixed(4).split('.');

            return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (parts[1].replace(/0+$/, '') ? '.' + parts[1].replace(/0+$/, '') : '');
        }

        function iqDate(value) {
            var p = String(value || '').substr(0, 10).split('-');

            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : '—';
        }

        function iqFill(r) {
            iqLimit = r.limit;

            $m.find('.iq-code').text(r.code || '—');
            $m.find('.iq-chem').text((r.chem_name || '—') + (r.batch_no ? ' · Lô ' + r.batch_no : ''));
            $m.find('.iq-location').text(r.location_code || 'Chưa xếp vị trí');
            $m.find('.iq-expiry').text(r.expired_date
                ? iqDate(r.expired_date) + (r.days < 0 ? ' (quá ' + Math.abs(r.days) + ' ngày)' : ' (còn ' + r.days + ' ngày)')
                : '—');
            $m.find('.iq-limit').text(iqNum(r.limit) + ' ' + (r.unit || ''));
            $m.find('.iq-unit').text(r.unit || '—');
            $m.find('[name="items[0][import_id]"]').val(r.import_id);
            $m.find('[name="lot_info"]').val(JSON.stringify(r));
        }

        // Số lượng phải > 0 và không vượt tồn hiện tại của mã
        function iqCheck() {
            var $input = $m.find('[name="items[0][amount]"]');
            var amount = parseFloat($input.val());

            $input.toggleClass('is-invalid', !(amount > IQ_EPS) || (iqLimit !== null && amount > iqLimit + IQ_EPS));
        }

        $m.on('input', '[name="items[0][amount]"]', iqCheck);

        $(document).on('click', '.btn-inv-quarantine', function () {
            var r = $(this).data('row') || {};
            var expiringTab = $(this).closest('.inv-pane').attr('id') === 'invPaneExpiring';

            iqFill(r);

            $m.find('[name="return_tab"]').val(expiringTab ? 'expiring' : 'code');
            $m.find('[name="items[0][amount]"]').val(r.limit).removeClass('is-invalid');
            // Lý do điền sẵn theo hạn dùng (vẫn sửa được)
            $m.find('[name="reason"]').val(r.days !== null && r.days !== undefined && r.expired_date
                ? (r.days < 0 ? 'Hết hạn sử dụng' : (expiringTab ? 'Sắp hết hạn sử dụng' : ''))
                : '');
            $m.modal('show');
        });

        $('#invQuarantineForm').on('submit', function (e) {
            iqCheck();

            if ($m.find('[name="items[0][amount]"]').hasClass('is-invalid')) {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa cách ly được',
                    text: 'Số lượng cách ly phải lớn hơn 0 và không vượt tồn hiện tại của mã xuất nhập.',
                    confirmButtonText: 'Đã hiểu'
                });
            }
        });

        @if ($iqBag->any())
            (function () {
                var info = {};

                try { info = JSON.parse(@json((string) old('lot_info')) || '{}'); } catch (err) {}

                iqFill(info);
                $m.find('[name="items[0][amount]"]').val(@json((string) old('items.0.amount')));
                $m.modal('show');
            })();
        @endif
    });
</script>
@endperm
