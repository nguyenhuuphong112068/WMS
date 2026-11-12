{{--
| SỬ DỤNG VPP - modal ghi phiếu sử dụng (cơ chế "Sử dụng - ghi sổ" như Hoá Chất, từ 12/11/2026)
|
| Khối 1: chọn lô - quét mã xuất nhập trên nhãn (máy quét / camera / gõ tay) hoặc chọn từ bảng tồn
|         kho của phòng ngay bên dưới (không mở modal lồng).
| Khối 2: các lô đã chọn, mỗi dòng nhập Số lượng dùng + Mục đích.
| Lưu là ghi ngay vào Sổ sử dụng VPP và trừ tồn - không có Phiếu Tạm.
|
| Dữ liệu lô ($useLots) do VppExportController::index() nạp: lô đã kiểm tra nhập, còn tồn. Tra mã
| quét được làm ngay trên trình duyệt theo bộ lô này; server kiểm lại tồn / hạn khi Lưu (store()).
--}}

@php
    $vuBag = $errors->getBag('createErrors');

    $vuLots = $useLots->map(fn ($lot) => [
        'id' => (int) $lot->id,
        'code' => $lot->code,
        'name' => $lot->material_name,
        'category_code' => $lot->category_code ?? null,
        'unit' => $lot->unit_short_name,
        'remaining' => (float) $lot->remaining,
        'available' => (float) $lot->available,
        'expired' => (bool) $lot->expired,
        'expired_date' => $lot->expired_date ? \Carbon\Carbon::parse($lot->expired_date)->format('d/m/Y') : null,
        'expiry_level' => $lot->expiry_level,
        'location' => $lot->location_code,
    ])->values();

    $vuSelectable = $useLots->where('expired', false)->count();
@endphp

<style>
    #vppUseModal .vu-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 10px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #vppUseModal .vu-step {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: var(--primary);
        color: #fff;
        font-size: 0.75rem;
    }

    #vppUseModal .vu-pill {
        padding: 2px 10px;
        border-radius: 999px;
        background: var(--primary-soft);
        border: 1px solid var(--primary-lighter);
        color: var(--primary-dark);
        font-size: 0.75rem;
        text-transform: none;
        letter-spacing: 0;
    }

    #vppUseModal .vu-pick-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 300px);
        gap: 14px;
        margin-bottom: 14px;
    }

    #vppUseModal .vu-pick-grid .exp-scan {
        margin-bottom: 0;
    }

    #vppUseModal .vu-method {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 12px 14px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: var(--primary-soft);
    }

    #vppUseModal .vu-method>label {
        display: block;
        margin-bottom: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    #vppUseModal .vu-method .btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
    }

    #vppUseModal .vu-method .btn:hover {
        transform: translateY(-1px);
    }

    #vppUseModal .vu-badge {
        padding: 1px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.25);
        font-size: 0.75rem;
    }

    #vppUseModal .vu-picker {
        display: none;
        margin-bottom: 22px;
        padding: 12px;
        border: 1px solid var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        background: #fff;
    }

    #vppUseModal .vu-picker.is-open {
        display: block;
    }

    #vppUseModal .vu-picker-scroll {
        max-height: 40vh;
        overflow-y: auto;
        border: 1px solid #E2E8F0;
        border-radius: var(--border-radius-md);
    }

    #vppUseModal .vu-picker-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--primary-soft);
        color: var(--primary-dark);
        white-space: nowrap;
    }

    #vppUseModal table.vu-table {
        font-size: 0.86rem;
    }

    #vppUseModal table.vu-table td {
        vertical-align: middle;
    }

    #vppUseModal .vu-code {
        font-family: Consolas, monospace;
        font-weight: 700;
        color: var(--primary-dark);
        white-space: nowrap;
    }

    #vppUseModal .vu-num {
        text-align: right;
        white-space: nowrap;
        font-weight: 600;
    }

    #vppUseModal .vu-sub {
        color: #64748B;
        font-size: 0.78rem;
        font-weight: 400;
    }

    #vppUseModal tr.is-added {
        background: #F0FDF4;
    }

    #vppUseModal tr.is-expired {
        color: #94A3B8;
    }

    #vppUseModal .vu-empty {
        padding: 22px;
        border: 1px dashed var(--primary-lighter);
        border-radius: var(--border-radius-lg);
        text-align: center;
        color: #64748B;
    }

    #vppUseModal .vu-empty i {
        display: block;
        margin-bottom: 6px;
        font-size: 1.4rem;
        color: var(--primary-lighter);
    }

    #vppUseModal .vu-amount-msg {
        font-size: 0.75rem;
        margin-top: 2px;
    }

    @media (max-width: 767.98px) {
        #vppUseModal .vu-pick-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="modal fade md-modal" id="vppUseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 94vw;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="{{ $expIcon }} mr-2"></i>Sử Dụng VPP</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <form action="{{ route($expRoute . 'store') }}" method="POST" id="vppUseForm">
                @csrf
                <div class="modal-body">
                    @if ($vuBag->any())
                        <div class="alert alert-danger">
                            <b>Không lưu được, kiểm tra lại:</b>
                            <ul class="mb-0">
                                @foreach ($vuBag->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ===== KHỐI 1: CHỌN VPP CẦN DÙNG ===== --}}
                    <div class="vu-section-title">
                        <span class="vu-step">1</span> Chọn VPP cần dùng
                    </div>

                    <div class="vu-pick-grid">
                        <div class="exp-scan scan-box">
                            <label><i class="fas fa-barcode mr-1"></i> Cách 1 · Quét mã xuất nhập</label>
                            <div class="exp-scan-row">
                                <input type="text" class="form-control vu-scan-input" autocomplete="off"
                                    placeholder="Đưa máy quét vào mã vạch trên nhãn, hoặc gõ mã rồi nhấn Enter">
                                <button type="button" class="btn btn-outline-primary btn-camera-scan" title="Quét bằng camera">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button type="button" class="btn btn-primary vu-btn-scan">
                                    <i class="fas fa-search mr-1"></i> Tra mã
                                </button>
                            </div>
                            <div class="exp-scan-result vu-scan-result"></div>
                        </div>

                        <div class="vu-method">
                            <label><i class="fas fa-boxes-stacked mr-1"></i> Cách 2 · Tồn kho của phòng</label>
                            <button type="button" class="btn btn-primary vu-btn-picker"
                                title="Mở / đóng bảng các lô còn tồn của phòng">
                                <i class="fas fa-list-check"></i> Chọn Từ Tồn Kho
                                <span class="vu-badge">{{ number_format($vuSelectable) }} lô</span>
                            </button>
                        </div>
                    </div>

                    <div class="vu-picker">
                        <div class="input-group input-group-sm mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control vu-picker-search"
                                placeholder="Tìm theo mã xuất nhập, mã VPP, tên VPP, vị trí...">
                        </div>
                        <div class="vu-picker-scroll">
                            <table class="table table-sm table-hover mb-0 vu-table" data-no-datatable>
                                <thead>
                                    <tr>
                                        <th style="width: 150px">Mã Xuất Nhập</th>
                                        <th class="md-col-name">Tên VPP</th>
                                        <th style="width: 100px">Mã VPP</th>
                                        <th style="width: 110px">Vị Trí</th>
                                        <th style="width: 100px" class="text-center">Hạn Dùng</th>
                                        <th style="width: 130px" class="text-right">Tồn Còn</th>
                                        <th style="width: 70px"></th>
                                    </tr>
                                </thead>
                                <tbody class="vu-picker-body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ===== KHỐI 2: VPP ĐÃ CHỌN ===== --}}
                    <div class="vu-section-title">
                        <span class="vu-step">2</span> VPP đã chọn
                        <span class="vu-pill vu-rows-count">0 dòng</span>
                    </div>

                    <div class="table-responsive vu-rows-wrap" style="display: none">
                        <table class="table table-bordered table-sm md-table mb-0 vu-table" data-no-datatable>
                            <thead>
                                <tr>
                                    <th style="width: 40px" class="text-center">#</th>
                                    <th class="md-col-name">Tên VPP</th>
                                    <th style="width: 150px">Mã Xuất Nhập</th>
                                    <th style="width: 130px" class="text-right">Tồn Còn</th>
                                    <th style="width: 100px" class="text-center">Hạn Dùng</th>
                                    <th style="width: 200px">Số Lượng Dùng <span class="text-danger">*</span></th>
                                    <th style="min-width: 240px">Mục Đích</th>
                                    <th style="width: 44px"></th>
                                </tr>
                            </thead>
                            <tbody class="vu-rows"></tbody>
                        </table>
                    </div>
                    <div class="vu-empty">
                        <i class="fas fa-paperclip"></i>
                        Chưa chọn VPP nào. Quét mã hoặc bấm <b>Chọn Từ Tồn Kho</b> ở trên để thêm.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary vu-submit">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal quét camera dùng chung - đặt ngoài #vppUseModal để không lồng modal trong modal --}}
@include('pages.shared.cameraScan')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var lots = @json($vuLots);
        var overRatio = {{ $overIssuePercent }} / 100;
        var oldItems = @json($vuBag->any() ? (object) old('items', []) : (object) []);

        var byId = {};
        var byCode = {};
        lots.forEach(function (lot) {
            byId[lot.id] = lot;
            byCode[String(lot.code).toLowerCase()] = lot;
        });

        var $modal = $('#vppUseModal');
        var $rows = $modal.find('.vu-rows');
        var $picker = $modal.find('.vu-picker');
        var $pickerBody = $modal.find('.vu-picker-body');

        function fmt(value) {
            var n = Number(value) || 0;

            return n.toLocaleString('en-US', { maximumFractionDigits: 4 });
        }

        function syncRows() {
            var count = $rows.children('tr').length;

            $rows.children('tr').each(function (index) {
                $(this).find('.vu-row-no').text(index + 1);
            });
            $modal.find('.vu-rows-count').text(count + ' dòng');
            $modal.find('.vu-rows-wrap').toggle(count > 0);
            $modal.find('.vu-empty').toggle(count === 0);

            // Đánh dấu lô đã thêm trên bảng chọn
            $pickerBody.children('tr').each(function () {
                var added = $rows.children('tr[data-id="' + $(this).data('id') + '"]').length > 0;
                $(this).toggleClass('is-added', added);
                $(this).find('.vu-pick').prop('disabled', added || $(this).hasClass('is-expired'))
                    .html(added ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>');
            });
        }

        function checkAmount($row) {
            var lot = byId[$row.data('id')];
            var $input = $row.find('.vu-amount');
            var $msg = $row.find('.vu-amount-msg');
            var value = parseFloat(String($input.val()).replace(',', '.'));
            var limit = lot.available * (1 + overRatio);

            $input.removeClass('is-invalid');
            $msg.removeClass('text-danger text-warning').text('');

            if ($input.val() === '') return;

            if (!(value > 0)) {
                $input.addClass('is-invalid');
                $msg.addClass('text-danger').text('Số lượng phải lớn hơn 0.');
            } else if (value > limit + 0.00005) {
                $input.addClass('is-invalid');
                $msg.addClass('text-danger').text('Vượt tồn cho phép, tối đa ' + fmt(limit) + '.');
            } else if (value > lot.available + 0.00005) {
                $msg.addClass('text-warning').text('Vượt tồn còn, trong mức ' + Math.round(overRatio * 100) + '% cho phép.');
            }
        }

        function addRow(lot, values) {
            var $exists = $rows.children('tr[data-id="' + lot.id + '"]');

            if ($exists.length) {
                $exists.find('.vu-amount').trigger('focus');

                return $exists;
            }

            var name = 'items[' + lot.id + ']';
            var $row = $('<tr>').attr('data-id', lot.id);

            $row.append($('<td>').addClass('text-center vu-row-no'));
            $row.append($('<td>')
                .append($('<div>').addClass('font-weight-bold').text(lot.name || '—'))
                .append(lot.category_code ? $('<div>').addClass('vu-sub').text(lot.category_code) : ''));
            $row.append($('<td>')
                .append($('<span>').addClass('vu-code').text(lot.code))
                .append(lot.location ? $('<div>').addClass('vu-sub').text(lot.location) : ''));
            $row.append($('<td>').addClass('vu-num')
                .text(fmt(lot.available) + ' ')
                .append($('<span>').addClass('vu-sub').text(lot.unit || '')));
            $row.append($('<td>').addClass('text-center').text(lot.expired_date || '—'));
            $row.append($('<td>')
                .append($('<div>').addClass('input-group input-group-sm')
                    .append($('<input>').attr({
                        type: 'text', inputmode: 'decimal', name: name + '[amount]', required: true, autocomplete: 'off'
                    }).addClass('form-control vu-amount js-decimal').val(values.amount || ''))
                    .append($('<div>').addClass('input-group-append')
                        .append($('<span>').addClass('input-group-text').text(lot.unit || '—'))))
                .append($('<div>').addClass('vu-amount-msg')));
            $row.append($('<td>')
                .append($('<textarea>').attr({ name: name + '[purpose]', rows: 1, maxlength: 500, placeholder: 'Mục đích sử dụng...' })
                    .addClass('form-control form-control-sm').val(values.purpose || '')));
            $row.append($('<td>').addClass('text-center')
                .append($('<button>').attr({ type: 'button', title: 'Bỏ dòng' }).addClass('btn btn-sm btn-outline-danger vu-remove')
                    .append($('<i>').addClass('fas fa-times'))));

            $rows.append($row);
            syncRows();
            checkAmount($row);

            return $row;
        }

        function renderPicker() {
            var html = $();

            lots.forEach(function (lot) {
                var $tr = $('<tr>').attr('data-id', lot.id).toggleClass('is-expired', lot.expired)
                    .attr('data-search', [lot.code, lot.category_code, lot.name, lot.location].join(' ').toLowerCase());

                $tr.append($('<td>').append($('<span>').addClass('vu-code').text(lot.code)));
                $tr.append($('<td>').addClass('font-weight-bold').text(lot.name || '—'));
                $tr.append($('<td>').text(lot.category_code || '—'));
                $tr.append($('<td>').text(lot.location || '—'));
                $tr.append($('<td>').addClass('text-center')
                    .append(lot.expired
                        ? $('<span>').addClass('badge badge-danger').text('Hết hạn ' + lot.expired_date)
                        : $('<span>').addClass(lot.expiry_level ? 'text-warning font-weight-bold' : '').text(lot.expired_date || '—')));
                $tr.append($('<td>').addClass('vu-num').text(fmt(lot.available) + ' ')
                    .append($('<span>').addClass('vu-sub').text(lot.unit || '')));
                $tr.append($('<td>').addClass('text-center')
                    .append($('<button>').attr({ type: 'button', title: lot.expired ? 'Lô đã hết hạn' : 'Thêm lô này' })
                        .addClass('btn btn-sm btn-primary vu-pick').prop('disabled', lot.expired)
                        .append($('<i>').addClass('fas fa-plus'))));

                html = html.add($tr);
            });

            if (!lots.length) {
                html = $('<tr>').append($('<td colspan="7" class="text-center text-muted py-3">').text('Phòng chưa có lô VPP nào còn tồn.'));
            }

            $pickerBody.empty().append(html);
        }

        function scanResult(kind, text) {
            $modal.find('.vu-scan-result').removeClass('ok fail busy').addClass('is-shown ' + kind).text(text);
        }

        function scan() {
            var $input = $modal.find('.vu-scan-input');
            var code = ($input.val() || '').trim();

            if (!code) return;

            var lot = byCode[code.toLowerCase()];

            if (!lot) {
                scanResult('fail', 'Không tìm thấy mã xuất nhập "' + code + '" còn tồn trong kho của phòng.');
                $input.select();

                return;
            }

            if (lot.expired) {
                scanResult('fail', 'Mã xuất nhập ' + lot.code + ' đã hết hạn (' + lot.expired_date + '), không sử dụng được.');
                $input.select();

                return;
            }

            addRow(lot, {}).find('.vu-amount').trigger('focus');
            scanResult('ok', lot.code + ' · ' + (lot.name || '') + ' · Tồn còn: ' + fmt(lot.available) + ' ' + (lot.unit || ''));
            $input.val('');
        }

        // Máy quét / camera kết thúc bằng Enter - chặn để Enter không gửi luôn cả form
        $modal.on('keydown', '.vu-scan-input', function (e) {
            if (e.key !== 'Enter') return;

            e.preventDefault();
            scan();
        });
        $modal.on('click', '.vu-btn-scan', scan);

        $modal.on('click', '.vu-btn-picker', function () {
            $picker.toggleClass('is-open');
            if ($picker.hasClass('is-open')) $picker.find('.vu-picker-search').trigger('focus');
        });

        $modal.on('input', '.vu-picker-search', function () {
            var q = ($(this).val() || '').toLowerCase().trim();

            $pickerBody.children('tr[data-id]').each(function () {
                $(this).toggle(!q || String($(this).data('search')).indexOf(q) !== -1);
            });
        });

        $modal.on('click', '.vu-pick', function () {
            var lot = byId[$(this).closest('tr').data('id')];
            if (lot && !lot.expired) addRow(lot, {});
        });

        $modal.on('click', '.vu-remove', function () {
            $(this).closest('tr').remove();
            syncRows();
        });

        $modal.on('input', '.vu-amount', function () {
            checkAmount($(this).closest('tr'));
        });

        // Enter trong ô số lượng / mục đích không gửi form khi chưa nhập xong các dòng khác
        $modal.on('keydown', '.vu-amount', function (e) {
            if (e.key === 'Enter') e.preventDefault();
        });

        $modal.find('form').on('submit', function (e) {
            if (!$rows.children('tr').length) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Chưa chọn VPP', text: 'Quét mã hoặc chọn ít nhất một lô từ tồn kho của phòng.' });

                return;
            }

            if ($rows.find('.vu-amount.is-invalid').length) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Số lượng chưa hợp lệ', text: 'Kiểm tra lại các ô Số Lượng Dùng đang báo đỏ.' });

                return;
            }

            $(this).find('.vu-submit').prop('disabled', true);
        });

        // Nút "Sử dụng VPP" ở tab Sử Dụng VPP: mở modal trắng
        $(document).on('click', '.btn-vpp-use', function () {
            $rows.empty();
            $picker.removeClass('is-open').find('.vu-picker-search').val('').trigger('input');
            $modal.find('.vu-scan-input').val('');
            $modal.find('.vu-scan-result').removeClass('is-shown ok fail busy').empty();
            $modal.find('.alert-danger').remove();
            $modal.find('.vu-submit').prop('disabled', false);
            syncRows();
            $modal.modal('show');
        });

        $modal.on('shown.bs.modal', function () {
            $modal.find('.vu-scan-input').trigger('focus');
        });

        renderPicker();
        syncRows();

        // Lưu bị lỗi: dựng lại đúng các dòng đã nhập rồi mở lại modal
        if (Object.keys(oldItems).length) {
            Object.keys(oldItems).forEach(function (id) {
                if (byId[id]) addRow(byId[id], oldItems[id] || {});
            });
            $modal.modal('show');
        }
    });
</script>
