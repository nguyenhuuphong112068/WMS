{{--
| SỬ DỤNG CHẤT CHUẨN - MODAL CỦA TAB "CÁCH LY - LOẠI BỎ - HUỶ" (cùng khuôn Hoá Chất)
|   #qrEditModal    : sửa số lượng / lý do / định khu khi còn đang cách ly
|   #qrDecideModal  : bước 2 - quyết định loại bỏ / trả về kho (ký bằng mật khẩu)
|   #qrDisposeModal : bước 3 - ghi nhận huỷ một hoặc nhiều phiếu (ký bằng mật khẩu)
| Bước 1 - Cách ly mở từ nút [Cách ly] ở Tồn Kho Chất Chuẩn (inventory/StandardInventory/quarantine).
--}}

@php
    $qrRoute = 'pages.export.standardQuarantine.';
    $qrDecideBag = $errors->getBag('quarantineDecideErrors');
    $qrDisposeBag = $errors->getBag('quarantineDisposeErrors');
    $qrSignError = (string) session('signatureError');

    // Gợi ý phương pháp huỷ - vẫn gõ tự do được
    $qrMethods = ['Đốt', 'Hoà tan và xả vào hệ thống xử lý nước thải', 'Chuyển đơn vị xử lý chất thải nguy hại'];

    $qrPendingLocations = collect($quarantinePendingLocations ?? []);
    $qrRejectedLocations = collect($quarantineRejectedLocations ?? []);
    $qrReturnLocations = collect($quarantineReturnLocations ?? []);
    $qrLocationText = fn($loc) => $loc->code . ' — ' . implode(' / ', array_filter([$loc->warehouse_name, $loc->shelf_name, $loc->column_name, $loc->tier_name]));
@endphp

<style>
    .qr-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        border-radius: var(--border-radius-md, 8px);
        background: var(--primary-soft);
    }

    .qr-summary small {
        display: block;
        color: var(--primary-dark);
        opacity: 0.8;
    }

    .qr-choices {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .qr-choice {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 14px;
        margin: 0;
        border: 2px solid var(--primary-lighter);
        border-radius: var(--border-radius-md, 8px);
        cursor: pointer;
        font-weight: 400;
        transition: all 0.2s;
    }

    .qr-choice:hover {
        transform: translateY(-1px);
    }

    .qr-choice input {
        margin-top: 4px;
    }

    .qr-choice b {
        display: block;
    }

    .qr-choice.is-remove.is-checked {
        border-color: #DC2626;
        background: #FEF2F2;
    }

    .qr-choice.is-return.is-checked {
        border-color: #16A34A;
        background: #F0FDF4;
    }

    .qr-dispose-list {
        max-height: 200px;
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .qr-dispose-list li {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 10px;
        border-bottom: 1px dashed var(--primary-lighter);
    }

    @media (max-width: 575px) {
        .qr-choices {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- ============ SỬA PHIẾU ĐANG CÁCH LY ============ --}}
<div class="modal fade md-modal" id="qrEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Sửa phiếu cách ly <span class="qr-code"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'update') }}" method="POST">
                @csrf
                <input type="hidden" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Số lượng cách ly <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" inputmode="decimal" name="amount" class="form-control js-decimal" required>
                            <div class="input-group-append"><span class="input-group-text qr-unit">—</span></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Định khu Chờ Quyết Định <span class="text-danger">*</span></label>
                        <select name="pending_location_id" class="form-control wms-zone-select" required>
                            <option value="">-- Chọn vị trí đặt ống chuẩn cách ly --</option>
                            @foreach ($qrPendingLocations as $loc)
                                <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type }}">{{ $qrLocationText($loc) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Lý do cách ly <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" maxlength="500" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ BƯỚC 2 - QUYẾT ĐỊNH ============ --}}
<div class="modal fade md-modal" id="qrDecideModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel mr-2"></i>Quyết định chất chuẩn cách ly</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'decide') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="{{ old('id') }}">
                <div class="modal-body">
                    <div class="qr-summary">
                        <div><small>Phiếu cách ly</small><b class="exp-code qr-code">—</b></div>
                        <div><small>Chất chuẩn</small><b class="qr-chem">—</b></div>
                        <div><small>Mã ống chuẩn</small><b class="exp-code qr-import">—</b></div>
                        <div><small>Số lượng</small><b class="qr-amount">—</b></div>
                        <div><small>Định khu chờ quyết định</small><b class="exp-code qr-pending-location">—</b></div>
                        <div style="grid-column: 1 / -1"><small>Lý do cách ly</small><span class="qr-reason text-danger">—</span></div>
                    </div>

                    <div class="form-group">
                        <label>Quyết định <span class="text-danger">*</span></label>
                        <div class="qr-choices">
                            <label class="qr-choice is-remove">
                                <input type="radio" name="decision" value="remove" {{ old('decision') === 'remove' ? 'checked' : '' }}>
                                <span>
                                    <b class="text-danger"><i class="fas fa-trash-alt mr-1"></i> Loại bỏ</b>
                                    <small class="text-muted">Không thể quay lại kho. Chuyển sang chờ huỷ.</small>
                                </span>
                            </label>
                            <label class="qr-choice is-return">
                                <input type="radio" name="decision" value="return" {{ old('decision') === 'return' ? 'checked' : '' }}>
                                <span>
                                    <b class="text-success"><i class="fas fa-undo mr-1"></i> Trả về kho</b>
                                    <small class="text-muted">Chất chuẩn đạt, hoàn tồn và sử dụng lại bình thường.</small>
                                </span>
                            </label>
                        </div>
                        @if ($qrDecideBag->has('decision')) <div class="md-error text-danger small">{{ $qrDecideBag->first('decision') }}</div> @endif
                    </div>

                    <div class="form-group qr-rejected-location" style="display: none;">
                        <label>Định khu Loại Bỏ/Chờ Huỷ <span class="text-danger">*</span></label>
                        <select name="rejected_location_id" {{ $qrRejectedLocations->isEmpty() ? 'disabled' : '' }}
                            class="form-control wms-zone-select {{ $qrDecideBag->has('rejected_location_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn vị trí đặt ống chuẩn chờ huỷ --</option>
                            @foreach ($qrRejectedLocations as $loc)
                                <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type }}"
                                    {{ (string) old('rejected_location_id', $qrRejectedLocations->count() === 1 ? $loc->id : '') === (string) $loc->id ? 'selected' : '' }}>
                                    {{ $qrLocationText($loc) }}
                                </option>
                            @endforeach
                        </select>
                        @if ($qrRejectedLocations->isEmpty())
                            <div class="md-error text-danger small">Phòng ban chưa khai định khu chất chuẩn loại "Loại Bỏ/Chờ Huỷ" ở Dữ Liệu Gốc → Định Khu.</div>
                        @endif
                        @if ($qrDecideBag->has('rejected_location_id')) <div class="md-error text-danger small">{{ $qrDecideBag->first('rejected_location_id') }}</div> @endif
                    </div>

                    <div class="form-group qr-returned-location" style="display: none;">
                        <label>Định khu trả về <span class="text-danger">*</span></label>
                        <select name="returned_location_id"
                            class="form-control wms-zone-select {{ $qrDecideBag->has('returned_location_id') ? 'is-invalid' : '' }}">
                            <option value="">-- Chọn vị trí đặt lại ống chuẩn --</option>
                            @foreach ($qrReturnLocations as $loc)
                                <option value="{{ $loc->id }}" data-zone="{{ $loc->zone_type }}">{{ $qrLocationText($loc) }}</option>
                            @endforeach
                        </select>
                        @if ($qrDecideBag->has('returned_location_id')) <div class="md-error text-danger small">{{ $qrDecideBag->first('returned_location_id') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Nội dung / căn cứ quyết định <span class="text-danger">*</span></label>
                        <textarea name="decision_note" rows="2" maxlength="500" required
                            class="form-control {{ $qrDecideBag->has('decision_note') ? 'is-invalid' : '' }}"
                            placeholder="Ví dụ: Theo PKN / OOS / BCSL số ..., chất chuẩn không đạt">{{ old('decision_note') }}</textarea>
                        @if ($qrDecideBag->has('decision_note')) <div class="md-error text-danger small">{{ $qrDecideBag->first('decision_note') }}</div> @endif
                    </div>

                    {{-- Thành phần thứ 2 của chữ ký điện tử - 21 CFR Part 11 §11.200 --}}
                    <div class="form-group mb-0">
                        <label>Mật khẩu xác nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                            placeholder="Nhập lại mật khẩu đăng nhập để ký xác nhận" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-signature mr-1"></i> Ký xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ BƯỚC 3 - HUỶ ============ --}}
<div class="modal fade md-modal" id="qrDisposeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-fire mr-2"></i>Ghi nhận huỷ chất chuẩn đã loại bỏ</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($qrRoute . 'dispose') }}" method="POST">
                @csrf
                <div class="qr-dispose-ids">
                    @foreach ((array) old('ids', []) as $oldId)
                        <input type="hidden" name="ids[]" value="{{ $oldId }}">
                    @endforeach
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Phiếu được huỷ (<span class="qr-dispose-count">0</span>)</label>
                        <ul class="qr-dispose-list"></ul>
                        @if ($qrDisposeBag->has('ids')) <div class="md-error text-danger small">{{ $qrDisposeBag->first('ids') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Phương pháp huỷ <span class="text-danger">*</span></label>
                        <input type="text" name="destroy_method" maxlength="255" list="qrMethodList" required
                            class="form-control {{ $qrDisposeBag->has('destroy_method') ? 'is-invalid' : '' }}"
                            value="{{ old('destroy_method') }}" placeholder="Chọn gợi ý hoặc tự nhập">
                        <datalist id="qrMethodList">
                            @foreach ($qrMethods as $method)
                                <option value="{{ $method }}">
                            @endforeach
                        </datalist>
                        @if ($qrDisposeBag->has('destroy_method')) <div class="md-error text-danger small">{{ $qrDisposeBag->first('destroy_method') }}</div> @endif
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="destroy_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Số biên bản huỷ, đơn vị thực hiện...">{{ old('destroy_note') }}</textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label>Mật khẩu xác nhận <span class="text-danger">*</span></label>
                        <input type="password" name="sign_password" autocomplete="current-password" class="form-control"
                            placeholder="Nhập lại mật khẩu đăng nhập để ký xác nhận" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-signature mr-1"></i> Ký xác nhận huỷ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ---------- Sửa phiếu đang cách ly ---------- */
        $(document).on('click', '.btn-qr-edit', function () {
            var r = $(this).data('row') || {};
            var $m = $('#qrEditModal');

            $m.find('[name="id"]').val(r.id);
            $m.find('[name="amount"]').val(r.amount);
            $m.find('[name="reason"]').val(r.reason || '');
            $m.find('[name="pending_location_id"]').val(r.pending_location_id ? String(r.pending_location_id) : '').trigger('change');
            $m.find('.qr-code').text(r.code || '');
            $m.find('.qr-unit').text(r.unit || '—');
            $m.modal('show');
        });

        /* ---------- Bước 2: quyết định ---------- */
        // Chọn "Loại bỏ" mới hiện + bắt buộc ô định khu Loại Bỏ/Chờ Huỷ; "Trả về kho" thì chọn vị trí đặt lại
        function qrPaintChoices() {
            var $m = $('#qrDecideModal');
            var decision = $m.find('[name="decision"]:checked').val();

            $m.find('.qr-choice').each(function () {
                $(this).toggleClass('is-checked', $(this).find('input').prop('checked'));
            });

            $m.find('.qr-rejected-location').toggle(decision === 'remove');
            $m.find('[name="rejected_location_id"]').prop('required', decision === 'remove');
            $m.find('.qr-returned-location').toggle(decision === 'return');
            $m.find('[name="returned_location_id"]').prop('required', decision === 'return');
        }

        $(document).on('change', '#qrDecideModal [name="decision"]', qrPaintChoices);

        function qrFillDecide(r, keepInput) {
            var $m = $('#qrDecideModal');

            $m.find('[name="id"]').val(r.id);
            $m.find('.qr-code').text(r.code || '—');
            $m.find('.qr-chem').text(r.chem_name || '—');
            $m.find('.qr-import').text(r.import_code || '—');
            $m.find('.qr-amount').text(r.amount_text || '—');
            $m.find('.qr-reason').text(r.reason || '—');
            $m.find('.qr-pending-location').text(r.pending_location_code || '—');

            // Định khu trả về: mặc định vị trí của ống trước khi cách ly (thêm vào danh sách nếu ô đó không còn trong định khu dùng được)
            var $back = $m.find('[name="returned_location_id"]');
            $back.find('option.qr-lot-location').remove();
            if (r.location_id && !$back.find('option[value="' + r.location_id + '"]').length) {
                $back.append($('<option class="qr-lot-location">').val(r.location_id).attr('data-zone', r.location_zone_type || '').text((r.location_code || '') + ' — vị trí hiện tại của ống'));
            }
            $back.val(keepInput ? @json((string) old('returned_location_id')) : (r.location_id ? String(r.location_id) : '')).trigger('change');

            if (!keepInput) {
                $m.find('[name="decision"]').prop('checked', false);
                $m.find('[name="decision_note"]').val('');
            }

            $m.find('[name="sign_password"]').val('');
            qrPaintChoices();
            $m.modal('show');
        }

        $(document).on('click', '.btn-qr-decide', function () {
            qrFillDecide($(this).data('row') || {}, false);
        });

        /* ---------- Bước 3: huỷ một hoặc nhiều phiếu ---------- */
        function qrOpenDispose(rows) {
            var $m = $('#qrDisposeModal');
            var $ids = $m.find('.qr-dispose-ids').empty();
            var $list = $m.find('.qr-dispose-list').empty();

            rows.forEach(function (r) {
                $ids.append($('<input type="hidden" name="ids[]">').val(r.id));
                $list.append(
                    $('<li>')
                        .append($('<span>').append($('<b class="exp-code">').text(r.code)).append(document.createTextNode(' — ' + (r.chem_name || ''))))
                        .append($('<span class="text-nowrap">').text(r.amount_text || ''))
                );
            });

            $m.find('.qr-dispose-count').text(rows.length);
            $m.find('[name="sign_password"]').val('');
            $m.modal('show');
        }

        $(document).on('click', '.btn-qr-dispose', function () {
            qrOpenDispose([$(this).data('row') || {}]);
        });

        function qrPaintSelected() {
            var n = $('.qr-check:checked').length;
            $('#qrDisposeSelected').prop('disabled', n === 0).find('.qr-selected-count').text(n ? '(' + n + ')' : '');
            $('#qrCheckAll').prop('checked', n > 0 && n === $('.qr-check').length);
        }

        $(document).on('change', '.qr-check', qrPaintSelected);
        $(document).on('change', '#qrCheckAll', function () {
            $('.qr-check').prop('checked', $(this).prop('checked'));
            qrPaintSelected();
        });
        $(document).on('click', '#qrDisposeSelected', function () {
            var rows = $('.qr-check:checked').map(function () { return $(this).data('row'); }).get();
            if (rows.length) qrOpenDispose(rows);
        });

        /* ---------- Lỗi validate / sai mật khẩu: mở lại đúng modal ---------- */
        @if ($qrDecideBag->any() || str_starts_with($qrSignError, 'Quyết định '))
            (function () {
                var oldId = @json(old('id'));
                var $btn = $('.btn-qr-decide').filter(function () {
                    return String(($(this).data('row') || {}).id) === String(oldId);
                }).first();

                if ($btn.length) qrFillDecide($btn.data('row') || {}, true);
            })();
        @endif

        @if ($qrDisposeBag->any() || $qrSignError === 'Ghi nhận huỷ chất chuẩn')
            (function () {
                var oldIds = @json(array_map('strval', (array) old('ids', [])));
                var rows = $('.qr-check, .btn-qr-dispose').map(function () { return $(this).data('row'); }).get()
                    .filter(function (r, i, all) {
                        return r && oldIds.indexOf(String(r.id)) !== -1
                            && all.findIndex(function (x) { return x && x.id === r.id; }) === i;
                    });

                if (rows.length) qrOpenDispose(rows);
            })();
        @endif
    });
</script>
