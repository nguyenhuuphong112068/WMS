{{--
|--------------------------------------------------------------------------
| CẤP PHÁT - CHỌN MÃ XUẤT NHẬP (một dòng đề nghị, nhiều lô)
|--------------------------------------------------------------------------
| Nút nhỏ cạnh mỗi ô cấp phát ở phiếu chi tiết mở bảng này: danh mục tồn của ĐÚNG mã vật
| tư đang cấp, đã sắp theo thứ tự nên xuất của App\Support\MaterialPicking và gắn badge
| lý do (FEFO cho lô có hạn dùng, FIFO cho lô không hạn).
|
| Một lô không đủ thì lấy tiếp lô sau: bảng cộng dồn số đã chọn, đủ số đề nghị thì báo
| xanh. Bấm "Dùng các lô đã chọn" là các dòng cấp phát của mục đó được dựng lại theo đúng
| những gì đã chọn - mỗi lô một dòng, gửi lên thành mảng lots[].
--}}

@php
    // Dữ liệu lô cho JS: gom theo danh mục, giữ nguyên thứ tự nên xuất mà controller trả về.
    $meLotPayload = ($lotsByCategory ?? collect())->map(function ($lots) {
        $rank = 0;

        return $lots->map(function ($lot) use (&$rank) {
            $selectable = (bool) $lot->selectable;

            if ($selectable) {
                $rank++;
            }

            return [
                'id' => (int) $lot->id,
                'code' => $lot->code,
                'available' => round((float) $lot->available, 4),
                'remaining' => round((float) $lot->remaining, 4),
                'held' => round((float) $lot->held, 4),
                'unit' => $lot->unit_short_name ?: '',
                'location' => $lot->location_code ?: '',
                'expired' => $lot->expired_date ? \Carbon\Carbon::parse($lot->expired_date)->format('d/m/Y') : '',
                'imported' => $lot->imported_date ? \Carbon\Carbon::parse($lot->imported_date)->format('d/m/Y') : '',
                'days' => $lot->days_to_expiry,
                'level' => $lot->expiry_level,          // null | warning | critical | expired
                'rule' => $lot->expired_date ? 'FEFO' : 'FIFO',
                'selectable' => $selectable,
                'rank' => $selectable ? $rank : 0,      // 1 = lô hệ thống đề xuất trước nhất
            ];
        })->values();
    });
@endphp

<style>
    /* ---------- Dòng cấp phát trong phiếu chi tiết ---------- */
    .me-issue-line { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
    .me-issue-line .me-issue-lot { flex: 1 1 230px; min-width: 170px; }
    .me-issue-line .me-issue-amount { width: 92px; flex: 0 0 92px; }

    /*
    | .md-modal .form-control đặt padding 9px trong khi .form-control-sm của Bootstrap ghim
    | height cố định ~31px - chữ trong ô chọn mã xuất nhập bị cắt mất nửa trên. Trả height
    | về auto để ô tự cao theo nội dung.
    */
    .me-issue-line .form-control-sm,
    .me-issue-foot .form-control-sm,
    #meLotPickerTable .form-control-sm {
        height: auto;
        min-height: 32px;
        padding: 5px 9px;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .me-issue-line .me-issue-lot option { font-size: 0.85rem; }
    .me-issue-line .btn-sm { line-height: 1.45; padding: 5px 9px; }
    .me-issue-note { margin: -2px 0 8px 2px; font-size: 0.74rem; color: #6B7280; line-height: 1.7; }
    .me-issue-foot { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .me-issue-foot .me-issue-unit { width: 76px; }

    .me-issue-sum {
        flex: 1 1 100%;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        color: var(--primary-dark);
    }
    .me-issue-sum.is-short { background: #FEF3C7; color: #92400E; }
    .me-issue-sum.is-over { background: #FEE2E2; color: #991B1B; }

    /* ---------- Badge khuyến nghị FEFO / FIFO ---------- */
    .me-badge {
        display: inline-block;
        margin: 1px 3px 1px 0;
        padding: 1px 8px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .me-badge.best { background: var(--primary); color: #fff; }
    .me-badge.next { background: var(--primary-soft); color: var(--primary-dark); border-color: var(--primary-lighter); }
    .me-badge.fefo { background: #EDE9FE; color: #5B21B6; border-color: #DDD6FE; }
    .me-badge.fifo { background: #E0F2FE; color: #075985; border-color: #BAE6FD; }
    .me-badge.warn { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .me-badge.danger { background: #FEE2E2; color: #991B1B; border-color: #FCA5A5; }
    .me-badge.hold { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }
    .me-badge.off { background: #F3F4F6; color: #9CA3AF; border-color: #E5E7EB; }

    /* ---------- Bảng lô trong modal chọn ---------- */
    #meLotPickerTable tbody tr.is-off { background: #FAFAFA; color: #9CA3AF; }
    #meLotPickerTable tbody tr.is-taken { background: var(--primary-soft); }
    .me-lot-need {
        padding: 9px 12px;
        border-radius: var(--border-radius-md);
        background: var(--primary-soft);
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 0.86rem;
    }
    .me-lot-need.is-short { background: #FEF3C7; color: #92400E; }
    .me-lot-need.is-over { background: #FEE2E2; color: #991B1B; }
</style>

<div class="modal fade md-modal" id="meLotPickerModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 88vw;" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                    <i class="fas fa-layer-group mr-2"></i> Mã Xuất Nhập Có Thể Cấp Phát
                    <span class="text-muted font-weight-normal" id="meLotPickerMaterial"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body p-3">
                <div class="md-hint mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Thứ tự trong bảng là thứ tự <b>nên xuất</b>: lô có hạn dùng đi trước, hạn gần nhất trên cùng
                    (<b>FEFO</b>); vật tư không khai hạn dùng chạy theo ngày nhập sớm nhất (<b>FIFO</b>).
                    Một lô không đủ thì lấy tiếp lô kế dưới - <b>một mục được cấp từ nhiều mã xuất nhập</b>.
                </div>

                <div class="d-flex align-items-center justify-content-between flex-wrap mb-2" style="gap: 8px;">
                    <span class="me-lot-need" id="meLotPickerNeed">—</span>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="meLotPickerAuto">
                            <i class="fas fa-magic mr-1"></i> Phân bổ tự động theo FEFO/FIFO
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="meLotPickerClear">
                            <i class="fas fa-eraser mr-1"></i> Xoá chọn
                        </button>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 56vh; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover mb-0" id="meLotPickerTable" style="font-size: 0.86rem;">
                        <thead class="bg-light sticky-top">
                            <tr class="text-center">
                                <th style="width: 44px">#</th>
                                <th style="min-width: 165px" class="text-left">Mã Xuất Nhập</th>
                                <th style="min-width: 230px" class="text-left">Khuyến Nghị</th>
                                <th style="width: 105px">Hạn Dùng</th>
                                <th style="width: 105px">Ngày Nhập</th>
                                <th style="width: 105px">Vị Trí</th>
                                <th style="width: 130px" class="text-right">Còn Hứa Được</th>
                                <th style="width: 130px">Cấp Từ Lô Này</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-primary px-3 py-2" id="meLotPickerCount" style="font-size: 0.86rem;">
                    <i class="fas fa-check-circle mr-1"></i> Đã chọn: 0 mã xuất nhập
                </span>
                <div>
                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="meLotPickerConfirm">
                        <i class="fas fa-check mr-1"></i> Dùng các lô đã chọn
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.meIssueLots = @json($meLotPayload);

    document.addEventListener('DOMContentLoaded', function () {
        var EPS = 0.00005;
        var $picker = $('#meLotPickerModal');
        var $pickerBody = $('#meLotPickerTable tbody');
        var pickerForm = null;   // form cấp phát đang mở bảng chọn

        function num(value) {
            return String(Math.round((parseFloat(value) || 0) * 10000) / 10000);
        }

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function lotsOf(categoryId) {
            return (window.meIssueLots || {})[String(categoryId)] || [];
        }

        function lotById(categoryId, importId) {
            return lotsOf(categoryId).filter(function (lot) { return String(lot.id) === String(importId); })[0] || null;
        }

        /* Badge lý do nên xuất của một lô - dùng chung cho bảng chọn và dòng cấp phát. */
        function lotBadges(lot) {
            var html = '';

            if (!lot.selectable) {
                html += '<span class="me-badge off">không cấp được</span>';
            } else if (lot.rank === 1) {
                html += '<span class="me-badge best"><i class="fas fa-star mr-1"></i>NÊN XUẤT TRƯỚC</span>';
            } else {
                html += '<span class="me-badge next">ưu tiên #' + lot.rank + '</span>';
            }

            if (lot.rule === 'FEFO') {
                html += '<span class="me-badge fefo">FEFO · hạn ' + esc(lot.expired) + '</span>';
            } else {
                html += '<span class="me-badge fifo">FIFO · nhập ' + esc(lot.imported || '—') + '</span>';
            }

            if (lot.level === 'expired') {
                html += '<span class="me-badge danger">HẾT HẠN</span>';
            } else if (lot.level === 'critical') {
                html += '<span class="me-badge danger">SÁT HẠN còn ' + lot.days + ' ngày</span>';
            } else if (lot.level === 'warning') {
                html += '<span class="me-badge warn">cận hạn còn ' + lot.days + ' ngày</span>';
            }

            if (lot.held > EPS) {
                html += '<span class="me-badge hold">giữ ' + num(lot.held) + ' cho đợt lấy hàng</span>';
            }

            if (lot.selectable && lot.available <= EPS) {
                html += '<span class="me-badge off">hết tồn khả dụng</span>';
            }

            return html;
        }

        function lotOptionText(lot) {
            var text = (lot.rank === 1 ? '★ NÊN XUẤT — ' : '') + lot.code
                + ' (còn ' + num(lot.available) + ' ' + (lot.unit || '') + ')';

            if (lot.rule === 'FEFO') {
                text += ' · hạn ' + lot.expired;
            } else if (lot.imported) {
                text += ' · nhập ' + lot.imported;
            }

            if (lot.level === 'expired') {
                text += ' · HẾT HẠN';
            } else if (lot.level === 'critical') {
                text += ' · SÁT HẠN còn ' + lot.days + ' ngày';
            } else if (lot.level === 'warning') {
                text += ' · cận hạn còn ' + lot.days + ' ngày';
            }

            if (lot.held > EPS) {
                text += ' · giữ ' + num(lot.held) + ' cho đợt lấy hàng';
            }

            return text;
        }

        /* ---------- Dòng cấp phát của một mục đề nghị ---------- */

        function addLine($form, importId, amount) {
            var categoryId = $form.data('category-id');
            var index = parseInt($form.data('line-seq') || 0, 10);
            $form.data('line-seq', index + 1);

            var options = ['<option value="">-- Chọn mã xuất nhập --</option>'];

            lotsOf(categoryId).forEach(function (lot) {
                options.push(
                    '<option value="' + lot.id + '"' + (lot.selectable ? '' : ' disabled')
                    + (String(lot.id) === String(importId) ? ' selected' : '') + '>'
                    + esc(lotOptionText(lot)) + '</option>'
                );
            });

            $form.find('.me-issue-lines').append(
                '<div class="me-issue-line">'
                + '<select class="form-control form-control-sm me-issue-lot" name="lots[' + index + '][import_id]">' + options.join('') + '</select>'
                + '<input type="number" step="0.0001" min="0" class="form-control form-control-sm me-issue-amount"'
                + ' name="lots[' + index + '][amount]" value="' + (amount ? num(amount) : '') + '">'
                + '<button type="button" class="btn btn-sm btn-outline-primary me-issue-pick"'
                + ' title="Danh mục tồn có thể cấp phát của vật tư này"><i class="fas fa-layer-group"></i></button>'
                + '<button type="button" class="btn btn-sm btn-outline-secondary me-issue-drop"'
                + ' title="Bỏ dòng này"><i class="fas fa-times"></i></button>'
                + '</div>'
                + '<div class="me-issue-note"></div>'
            );
        }

        function syncForm($form) {
            var categoryId = $form.data('category-id');
            var needed = parseFloat($form.data('needed')) || 0;
            var unit = $form.data('unit') || '';
            var total = 0;
            var picked = 0;
            var seen = {};
            var duplicated = false;

            $form.find('.me-issue-line').each(function () {
                var $line = $(this);
                var importId = $line.find('.me-issue-lot').val();
                var amount = parseFloat($line.find('.me-issue-amount').val()) || 0;
                var lot = importId ? lotById(categoryId, importId) : null;
                var $note = $line.next('.me-issue-note');

                if (importId) {
                    if (seen[importId]) { duplicated = true; }

                    seen[importId] = true;
                }

                if (amount > EPS) {
                    total += amount;
                    picked++;
                }

                if (!lot) {
                    $note.empty();

                    return;
                }

                var html = lotBadges(lot)
                    + '<span class="ml-1">còn hứa được <b>' + num(lot.available) + ' ' + esc(lot.unit) + '</b>'
                    + (lot.location ? ' · vị trí ' + esc(lot.location) : '') + '</span>';

                if (amount > lot.available + EPS) {
                    html += '<span class="me-badge danger ml-1">vượt tồn khả dụng của lô</span>';
                }

                $note.html(html);
            });

            var short = needed - total;
            // Mục đã cấp một phần thì "needed" là phần CÒN THIẾU, nhãn đổi theo cho khỏi hiểu nhầm
            var needLabel = $form.data('need-label') || 'Đề nghị';
            var text = needLabel + ' <b>' + num(needed) + ' ' + esc(unit) + '</b> · đã phân bổ <b>' + num(total)
                + '</b> từ <b>' + picked + '</b> mã xuất nhập';
            var cls = '';

            if (short > EPS) {
                cls = 'is-short';
                text += ' · <b>còn thiếu ' + num(short) + '</b> - thêm mã xuất nhập khác hoặc cấp một phần';
            } else if (short < -EPS) {
                cls = 'is-over';
                text += ' · <b>cấp vượt ' + num(-short) + '</b> so với số cần cấp';
            } else {
                text += ' · <b>đủ số đề nghị</b>';
            }

            if (duplicated) {
                cls = 'is-over';
                text += ' · <b>có mã xuất nhập bị chọn trùng</b> (hệ thống sẽ cộng dồn)';
            }

            $form.find('.me-issue-sum').removeClass('is-short is-over').addClass(cls).html(text);
        }

        // Dựng sẵn kế hoạch chia lô mà máy chủ đề xuất cho từng mục còn chờ cấp
        $('.me-issue-form').each(function () {
            var $form = $(this);
            var plan = $form.data('plan') || [];

            if (!plan.length) {
                addLine($form, '', '');
            } else {
                plan.forEach(function (line) { addLine($form, line.import_id, line.suggested_amount); });
            }

            syncForm($form);
        });

        $(document).on('change input', '.me-issue-lot, .me-issue-amount', function () {
            syncForm($(this).closest('.me-issue-form'));
        });

        $(document).on('click', '.me-issue-add', function () {
            var $form = $(this).closest('.me-issue-form');

            addLine($form, '', '');
            syncForm($form);
        });

        $(document).on('click', '.me-issue-drop', function () {
            var $form = $(this).closest('.me-issue-form');
            var $line = $(this).closest('.me-issue-line');

            if ($form.find('.me-issue-line').length <= 1) {
                $line.find('.me-issue-lot').val('');
                $line.find('.me-issue-amount').val('');
            } else {
                $line.next('.me-issue-note').remove();
                $line.remove();
            }

            syncForm($form);
        });

        $(document).on('submit', '.me-issue-form', function (e) {
            var total = 0;

            $(this).find('.me-issue-amount').each(function () {
                total += parseFloat($(this).val()) || 0;
            });

            if (total <= EPS) {
                e.preventDefault();
                alert('Vui lòng chọn mã xuất nhập và nhập số lượng cấp phát lớn hơn 0!');
            }
        });

        /* ---------- Bảng chọn mã xuất nhập ---------- */

        function pickerSync() {
            var needed = parseFloat($picker.data('needed')) || 0;
            var unit = $picker.data('unit') || '';
            var total = 0;
            var count = 0;

            $pickerBody.find('tr').each(function () {
                var amount = parseFloat($(this).find('.me-lot-take').val()) || 0;

                $(this).toggleClass('is-taken', amount > EPS);

                if (amount > EPS) {
                    total += amount;
                    count++;
                }
            });

            var short = needed - total;
            var text = 'Cần cấp <b>' + num(needed) + ' ' + esc(unit) + '</b> · đã chọn <b>' + num(total) + '</b>';
            var cls = '';

            if (short > EPS) {
                cls = 'is-short';
                text += ' · còn thiếu <b>' + num(short) + '</b>';
            } else if (short < -EPS) {
                cls = 'is-over';
                text += ' · vượt <b>' + num(-short) + '</b>';
            } else {
                text += ' · <b>đủ</b>';
            }

            $('#meLotPickerNeed').removeClass('is-short is-over').addClass(cls).html(text);
            $('#meLotPickerCount').html('<i class="fas fa-check-circle mr-1"></i> Đã chọn: ' + count + ' mã xuất nhập');
        }

        /* Rót số theo đúng thứ tự nên xuất: lô trên cùng lấy tối đa rồi mới xuống lô kế. */
        function pickerFill(auto) {
            var left = parseFloat($picker.data('needed')) || 0;

            $pickerBody.find('.me-lot-take').each(function () {
                var $take = $(this);
                var available = parseFloat($take.data('available')) || 0;
                var selectable = String($take.data('selectable')) === '1';

                if (!auto || !selectable || left <= EPS) {
                    $take.val('');

                    return;
                }

                var take = Math.min(left, available);
                left -= take;
                $take.val(take > EPS ? num(take) : '');
            });

            pickerSync();
        }

        function openPicker($form) {
            pickerForm = $form;

            var lots = lotsOf($form.data('category-id'));

            $picker.data('needed', $form.data('needed')).data('unit', $form.data('unit') || '');
            $('#meLotPickerMaterial').text($form.data('material') ? '— ' + $form.data('material') : '');

            $pickerBody.empty();

            if (!lots.length) {
                $pickerBody.append(
                    '<tr><td colspan="8" class="text-center text-muted py-4">'
                    + 'Vật tư này chưa có mã xuất nhập nào còn hiệu lực trong kho của phòng.</td></tr>'
                );
            }

            lots.forEach(function (lot, i) {
                var $row = $(
                    '<tr class="' + (lot.selectable ? '' : 'is-off') + '">'
                    + '<td class="text-center text-muted">' + (i + 1) + '</td>'
                    + '<td class="font-weight-bold">' + esc(lot.code) + '</td>'
                    + '<td>' + lotBadges(lot) + '</td>'
                    + '<td class="text-center">' + esc(lot.expired || '—') + '</td>'
                    + '<td class="text-center">' + esc(lot.imported || '—') + '</td>'
                    + '<td class="text-center">' + esc(lot.location || '—') + '</td>'
                    + '<td class="text-right font-weight-bold">' + num(lot.available) + ' ' + esc(lot.unit) + '</td>'
                    + '<td class="text-center"></td>'
                    + '</tr>'
                );

                $row.children('td').last().append(
                    $('<input type="number" step="0.0001" min="0" class="form-control form-control-sm me-lot-take">')
                        .attr('placeholder', lot.selectable ? '0' : '—')
                        .attr('max', lot.available)
                        .prop('disabled', !lot.selectable)
                        .data('import-id', lot.id)
                        .data('available', lot.available)
                        .data('selectable', lot.selectable ? 1 : 0)
                );

                $pickerBody.append($row);
            });

            // Giữ lại đúng những gì đang có trên form; chưa chọn gì thì rót sẵn theo FEFO/FIFO
            var current = {};
            var hasCurrent = false;

            $form.find('.me-issue-line').each(function () {
                var importId = $(this).find('.me-issue-lot').val();
                var amount = parseFloat($(this).find('.me-issue-amount').val()) || 0;

                if (importId && amount > EPS) {
                    current[importId] = (current[importId] || 0) + amount;
                    hasCurrent = true;
                }
            });

            if (hasCurrent) {
                $pickerBody.find('.me-lot-take').each(function () {
                    var amount = current[String($(this).data('import-id'))];

                    $(this).val(amount ? num(amount) : '');
                });

                pickerSync();
            } else {
                pickerFill(true);
            }

            $picker.modal('show');
        }

        $(document).on('click', '.me-issue-pick', function () {
            openPicker($(this).closest('.me-issue-form'));
        });

        $(document).on('input', '.me-lot-take', pickerSync);
        $('#meLotPickerAuto').on('click', function () { pickerFill(true); });
        $('#meLotPickerClear').on('click', function () { pickerFill(false); });

        $('#meLotPickerConfirm').on('click', function () {
            if (!pickerForm) {
                return;
            }

            var chosen = [];

            $pickerBody.find('.me-lot-take').each(function () {
                var amount = parseFloat($(this).val()) || 0;

                if (amount > EPS) {
                    chosen.push({ import_id: $(this).data('import-id'), amount: amount });
                }
            });

            if (!chosen.length) {
                alert('Vui lòng nhập số lượng cần lấy ở ít nhất một mã xuất nhập!');

                return;
            }

            pickerForm.find('.me-issue-lines').empty();
            chosen.forEach(function (line) { addLine(pickerForm, line.import_id, line.amount); });
            syncForm(pickerForm);

            $picker.modal('hide');
        });

        // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để phiếu chi tiết còn cuộn được
        $picker.on('hidden.bs.modal', function () {
            if ($('.modal.show').length) {
                $('body').addClass('modal-open');
            }
        });
    });
</script>
