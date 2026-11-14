{{--
|--------------------------------------------------------------------------
| CẤP PHÁT - QUÉT XÁC NHẬN MÃ XUẤT NHẬP
|--------------------------------------------------------------------------
| Bật / tắt: config material.issue_scan_required (MATERIAL_ISSUE_SCAN_REQUIRED).
|
| Trước khi cấp, người cấp phát phải QUÉT ĐÚNG NHÃN của từng mã xuất nhập phần mềm gợi ý
| (hoặc đã chọn) - để chắc chắn lấy đúng lô thật trên kệ. Cấp từ nhiều mã thì quét đủ
| từng mã. Chưa khớp đủ thì nút Cấp Phát khoá.
|
| Áp cho 2 kiểu "đơn vị cấp phát":
|   - form .me-issue-form (tab Chờ Cấp Phát + phiếu chi tiết đề nghị nội bộ), trừ form gửi
|     hiệu chuẩn (data-calibrate="1" - chưa cấp phát). Mã cần quét = các dòng lô có số lượng.
|   - hàng .mat-issue-row của phiếu đề nghị liên phòng ban (nút .btn-trigger-mat-transfer-issue).
|
| Nút "Quét mã" do JS tự chèn cạnh nút Cấp Phát. Quét bằng máy đọc mã rời (gõ mã + Enter)
| hoặc camera (pages.shared.cameraScan). Đủ mã thì gửi kèm scan_codes[] để
| MaterialExportController::issueScanError() kiểm lại.
--}}

@if (config('material.issue_scan_required', true))
    @include('pages.shared.cameraScan')

    <style>
        .me-scan-btn {
            white-space: nowrap;
        }

        .me-scan-btn.is-done {
            border-color: #16A34A;
            color: #16A34A;
            background: #F0FDF4;
        }

        .me-scan-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
            align-self: center;
            background: #F3F4F6;
            color: #6B7280;
            border: 1px dashed #CBD5E0;
        }

        .me-scan-chip.ok {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        .mt-code-cell .me-scan-chip {
            margin-top: 4px;
        }

        #meScanModal .me-scan-list {
            border: 1px solid #E2E8F0;
            border-radius: var(--border-radius-md);
            overflow: hidden;
        }

        #meScanModal .me-scan-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            transition: background 0.3s ease;
        }

        #meScanModal .me-scan-item+.me-scan-item {
            border-top: 1px solid #EDF2F7;
        }

        #meScanModal .me-scan-item .code {
            font-weight: 700;
            color: var(--primary-dark);
            letter-spacing: 0.3px;
        }

        #meScanModal .me-scan-item .meta {
            font-size: 0.8rem;
            color: var(--text-muted, #718096);
        }

        #meScanModal .me-scan-item .st {
            margin-left: auto;
        }

        #meScanModal .me-scan-item.is-ok {
            background: #F0FDF4;
        }

        #meScanModal .me-scan-item.flash {
            background: #BBF7D0;
        }

        #meScanModal .me-scan-input {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        #meScanModal .me-scan-msg {
            margin-top: 10px;
            padding: 9px 12px;
            border-radius: var(--border-radius-md);
            font-size: 0.88rem;
            font-weight: 600;
            display: none;
        }

        #meScanModal .me-scan-msg.ok {
            display: block;
            background: #DCFCE7;
            color: #15803D;
        }

        #meScanModal .me-scan-msg.bad {
            display: block;
            background: #FEE2E2;
            color: #B91C1C;
        }

        #meScanModal .me-scan-msg.info {
            display: block;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        #meScanModal .me-scan-progress {
            font-weight: 700;
            color: var(--primary-dark);
        }
    </style>

    <div class="modal fade md-modal" id="meScanModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1058;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h5 class="modal-title font-weight-bold text-primary" style="font-size: 1.05rem;">
                        <i class="fas fa-qrcode mr-2"></i> Quét Xác Nhận Mã Xuất Nhập
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2" style="gap: 6px;">
                        <span class="font-weight-bold" id="meScanMaterial"></span>
                        <span class="me-scan-progress" id="meScanProgress"></span>
                    </div>

                    <div class="me-scan-list mb-3" id="meScanList"></div>

                    <div class="input-group scan-box">
                        <input type="text" class="form-control me-scan-input" id="meScanInput" autocomplete="off"
                            placeholder="Quét nhãn lô (máy đọc mã hoặc camera)">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary btn-camera-scan" title="Quét bằng camera">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <div class="me-scan-msg" id="meScanMsg"></div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var EPS = 0.00005;
            var $modal = $('#meScanModal');
            var current = null; // đơn vị cấp phát đang mở modal quét
            var lastSubmitted = null; // đơn vị vừa gửi cấp phát - cấp xong thì xoá các mã đã quét

            function esc(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }

            function norm(code) {
                return String(code == null ? '' : code).trim().toUpperCase();
            }

            function num(value) {
                return String(Math.round((parseFloat(value) || 0) * 10000) / 10000);
            }

            function lotsOf(categoryId) {
                return (window.meIssueLots || {})[String(categoryId)] || [];
            }

            /* ---------- Đơn vị cấp phát ---------- */

            // $el: form .me-issue-form hoặc hàng .mat-issue-row; null nếu không cần quét
            function unitOf($el) {
                if (!$el || !$el.length) return null;

                if ($el.is('.me-issue-form')) {
                    return String($el.data('calibrate')) === '1' ? null : $el;
                }

                if ($el.is('.mat-issue-row') && $el.find('.btn-trigger-mat-transfer-issue').length) {
                    return $el;
                }

                return null;
            }

            function isTransfer($unit) {
                return $unit.is('.mat-issue-row');
            }

            // Các mã xuất nhập cần quét của đơn vị: [{id, code, amount, unit, location, zone}]
            function required($unit) {
                var list = [];

                if (isTransfer($unit)) {
                    var $display = $unit.find('.mat-pick-display');
                    var importId = $display.attr('data-import-id');
                    var code = $.trim($display.find('.font-weight-bold').first().text());
                    var amount = parseFloat($unit.find('.input-mat-issue-amount').val()) || 0;

                    if (importId && code && amount > EPS) {
                        list.push({ id: String(importId), code: code, amount: amount, unit: $unit.find('.select-mat-issue-unit').val() || '' });
                    }

                    return list;
                }

                var categoryId = $unit.data('category-id');
                var seen = {};

                $unit.find('.me-issue-line').each(function () {
                    var importId = String($(this).find('.me-issue-lot').val() || $(this).find('input[type=hidden][name$="[import_id]"]').val() || '');
                    var amount = parseFloat($(this).find('.me-issue-amount').val()) || 0;

                    if (!importId || amount <= EPS) return;

                    var lot = lotsOf(categoryId).filter(function (l) { return String(l.id) === importId; })[0];

                    if (!lot) return;

                    if (seen[importId]) {
                        seen[importId].amount += amount;

                        return;
                    }

                    seen[importId] = { id: importId, code: lot.code, amount: amount, unit: lot.unit, location: lot.location, zone: lot.zone };
                    list.push(seen[importId]);
                });

                return list;
            }

            function verified($unit) {
                var map = $unit.data('meScanOk');

                if (!map) {
                    map = {};
                    $unit.data('meScanOk', map);
                }

                return map;
            }

            function state($unit) {
                var req = required($unit);
                var ok = verified($unit);
                var done = req.filter(function (r) { return ok[r.id]; }).length;

                return { req: req, done: done, complete: req.length > 0 && done === req.length };
            }

            // Nút Cấp Phát của đơn vị (form tab Chờ Cấp Phát dùng nút ngoài form qua form="id")
            function submitButtons($unit) {
                if (isTransfer($unit)) return $unit.find('.btn-trigger-mat-transfer-issue');

                var $btns = $unit.find('button[type=submit]');
                var id = $unit.attr('id');

                if (id) {
                    var $scope = $unit.closest('tr');
                    $btns = $btns.add(($scope.length ? $scope : $(document)).find('button[form="' + id + '"]'));
                }

                return $btns;
            }

            function scanButton($unit) {
                var $btn = $unit.data('meScanBtn');

                // Đã chèn và còn gắn trong hàng (hàng DataTables ở trang khác vẫn giữ nút)
                if ($btn && $btn[0] && $btn[0].parentNode) return $btn;

                var $submit = submitButtons($unit).first();

                if (!$submit.length) return $();

                $btn = $('<button type="button" class="btn btn-sm btn-outline-primary me-scan-btn mr-1" title="Quét mã xuất nhập trên nhãn lô để xác nhận lấy đúng lô">' +
                    '<i class="fas fa-qrcode mr-1"></i>Quét mã <span class="me-scan-count"></span></button>');
                $btn.data('meScanUnit', $unit);
                $submit.before($btn);
                $unit.data('meScanBtn', $btn);

                return $btn;
            }

            // Cập nhật nút Quét mã, chip từng dòng lô, khoá / mở nút Cấp Phát
            function apply($unit) {
                $unit = unitOf($unit);

                if (!$unit) return;

                var st = state($unit);
                var ok = verified($unit);
                var $btn = scanButton($unit);
                var $submits = submitButtons($unit);

                $btn.toggle(st.req.length > 0)
                    .toggleClass('is-done', st.complete)
                    .find('.me-scan-count').text(st.done + '/' + st.req.length);
                $btn.find('i').attr('class', st.complete ? 'fas fa-check-double mr-1' : 'fas fa-qrcode mr-1');

                if (st.req.length) {
                    $submits.prop('disabled', !st.complete)
                        .attr('title', st.complete ? 'Cấp phát theo mã xuất nhập đã quét xác nhận' : 'Quét xác nhận đủ ' + st.req.length + ' mã xuất nhập trước khi cấp phát');
                }

                // Chip trạng thái quét cạnh từng mã xuất nhập
                if (isTransfer($unit)) {
                    var r = st.req[0];
                    var $cell = $unit.find('.mt-code-cell');
                    var $chip = $cell.find('.me-scan-chip');

                    if (!r) {
                        $chip.remove();
                    } else {
                        if (!$chip.length) $chip = $('<div class="me-scan-chip"></div>').appendTo($cell);
                        chip($chip, ok[r.id]);
                    }

                    return;
                }

                var categoryId = $unit.data('category-id');

                $unit.find('.me-issue-row').each(function () {
                    var $row = $(this);
                    var importId = String($row.find('.me-issue-lot').val() || '');
                    var amount = parseFloat($row.find('.me-issue-amount').val()) || 0;
                    var $chip = $row.children('.me-scan-chip');
                    var known = importId && amount > EPS && lotsOf(categoryId).some(function (l) { return String(l.id) === importId; });

                    if (!known) {
                        $chip.remove();

                        return;
                    }

                    if (!$chip.length) $chip = $('<span class="me-scan-chip"></span>').appendTo($row);
                    chip($chip, ok[importId]);
                });
            }

            function chip($chip, isOk) {
                $chip.toggleClass('ok', !!isOk).html(isOk
                    ? '<i class="fas fa-check-circle"></i> Đã quét khớp'
                    : '<i class="fas fa-qrcode"></i> Chưa quét');
            }

            function allUnits($scope) {
                var $units = $scope.find('.me-issue-form').add($scope.find('.mat-issue-row'));

                if ($.fn.dataTable) {
                    $($.fn.dataTable.tables()).each(function () {
                        if ($scope.is(document) || $.contains($scope[0], this)) {
                            $units = $units.add($(this).DataTable().rows().nodes().to$().find('.me-issue-form'));
                        }
                    });
                }

                return $units;
            }

            function applyAll($scope) {
                allUnits($scope || $(document)).each(function () { apply($(this)); });
            }

            /* ---------- Modal quét ---------- */

            function renderModal() {
                if (!current) return;

                var st = state(current);
                var ok = verified(current);
                var html = st.req.map(function (r) {
                    var isOk = !!ok[r.id];

                    return '<div class="me-scan-item' + (isOk ? ' is-ok' : '') + '" data-id="' + esc(r.id) + '">' +
                        '<div><div class="code">' + esc(r.code) + '</div>' +
                        '<div class="meta">Lấy ' + num(r.amount) + ' ' + esc(r.unit || '') +
                        (r.location !== undefined && window.wmsZoneTag ? ' · ' + window.wmsZoneTag(r.location, r.zone, 'Chưa xếp vị trí') : '') +
                        '</div></div>' +
                        '<span class="st">' + (isOk
                            ? '<span class="me-scan-chip ok"><i class="fas fa-check-circle"></i> Đã quét khớp</span>'
                            : '<span class="me-scan-chip"><i class="fas fa-qrcode"></i> Chưa quét</span>') + '</span>' +
                        '</div>';
                });

                $('#meScanList').html(html.join('') || '<div class="p-3 text-muted text-center">Chưa chọn mã xuất nhập nào để cấp.</div>');
                $('#meScanProgress').text('Đã khớp ' + st.done + '/' + st.req.length);
            }

            function message(type, html) {
                $('#meScanMsg').attr('class', 'me-scan-msg ' + type).html(html);
            }

            function open($unit) {
                current = $unit;

                var material = isTransfer($unit)
                    ? $.trim($unit.find('td').eq(1).find('.font-weight-bold').first().text())
                    : ($unit.data('material') || '');

                $('#meScanMaterial').text(material);
                $('#meScanInput').val('');
                message('', '');
                renderModal();
                $modal.modal('show');
            }

            // Tiếng bíp ngắn: đúng = cao, sai = trầm (máy kho thường không nhìn màn hình khi quét)
            function beep(ok) {
                try {
                    var Ctx = window.AudioContext || window.webkitAudioContext;
                    var ctx = beep.ctx || (beep.ctx = new Ctx());
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();

                    osc.frequency.value = ok ? 1200 : 300;
                    gain.gain.value = 0.08;
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start();
                    osc.stop(ctx.currentTime + (ok ? 0.12 : 0.35));
                } catch (e) {}
            }

            function handleCode(raw) {
                if (!current) return;

                var code = norm(raw);

                if (!code) return;

                var st = state(current);
                var ok = verified(current);
                var hit = st.req.filter(function (r) { return norm(r.code) === code; })[0];

                if (hit) {
                    if (ok[hit.id]) {
                        message('info', '<i class="fas fa-info-circle mr-1"></i> Mã ' + esc(hit.code) + ' đã quét rồi.');
                        beep(true);

                        return;
                    }

                    ok[hit.id] = true;
                    beep(true);
                    apply(current);
                    renderModal();
                    $('#meScanList .me-scan-item[data-id="' + hit.id + '"]').addClass('flash');
                    setTimeout(function () { $('#meScanList .me-scan-item').removeClass('flash'); }, 500);

                    var after = state(current);

                    if (after.complete) {
                        message('ok', '<i class="fas fa-check-double mr-1"></i> Đã khớp đủ ' + after.req.length + ' mã xuất nhập - có thể Cấp Phát.');
                        setTimeout(function () { $modal.modal('hide'); }, 900);
                    } else {
                        message('ok', '<i class="fas fa-check-circle mr-1"></i> Khớp mã ' + esc(hit.code) + '. Còn ' + (after.req.length - after.done) + ' mã cần quét.');
                    }

                    return;
                }

                beep(false);

                // Lô khác của cùng vật tư: lấy nhầm lô so với gợi ý
                var categoryId = isTransfer(current)
                    ? current.find('.btn-open-mat-picker').data('category-id')
                    : current.data('category-id');
                var other = lotsOf(categoryId).filter(function (l) { return norm(l.code) === code; })[0];

                if (other) {
                    message('bad', '<i class="fas fa-exclamation-triangle mr-1"></i> Mã ' + esc(other.code) +
                        ' là LÔ KHÁC của vật tư này, không phải lô đang chọn để cấp. Lấy đúng lô ' +
                        st.req.map(function (r) { return '<b>' + esc(r.code) + '</b>'; }).join(', ') +
                        ' hoặc đổi mã xuất nhập trên phiếu rồi quét lại.');
                } else {
                    message('bad', '<i class="fas fa-times-circle mr-1"></i> Mã ' + esc(raw) + ' không khớp mã xuất nhập nào cần cấp ở đây.');
                }
            }

            // Máy đọc mã rời gõ mã + Enter; camera (cameraScan) điền mã rồi bắn Enter giả
            $('#meScanInput').on('keydown', function (e) {
                if (e.key !== 'Enter') return;

                e.preventDefault();
                handleCode($(this).val());
                $(this).val('').trigger('focus');
            });

            $modal.on('shown.bs.modal', function () {
                $('#meScanInput').trigger('focus');
            });

            $modal.on('hidden.bs.modal', function () {
                current = null;

                // Bootstrap 4 gỡ modal-open khi đóng modal con - trả lại để phiếu chi tiết còn cuộn được
                if ($('.modal.show').length) $('body').addClass('modal-open');
            });

            $(document).on('click', '.me-scan-btn', function () {
                var $unit = $(this).data('meScanUnit');

                if ($unit) open($unit);
            });

            /* ---------- Chặn cấp phát khi chưa quét đủ (capture: chạy trước mọi handler khác) ---------- */

            function guard($unit, e) {
                var st = state($unit);

                if (!st.req.length || st.complete) return true;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                open($unit);
                message('bad', '<i class="fas fa-lock mr-1"></i> Quét xác nhận đủ ' + st.req.length + ' mã xuất nhập trước khi cấp phát.');

                return false;
            }

            function attachCodes($form, req) {
                $form.find('input.me-scan-code').remove();
                req.forEach(function (r) {
                    $form.append($('<input type="hidden" name="scan_codes[]" class="me-scan-code">').val(r.code));
                });
            }

            document.addEventListener('submit', function (e) {
                var $unit = unitOf($(e.target));

                if (!$unit || isTransfer($unit)) return;

                if (guard($unit, e)) {
                    attachCodes($unit, state($unit).req);
                    lastSubmitted = $unit;
                }
            }, true);

            document.addEventListener('click', function (e) {
                var btn = e.target.closest ? e.target.closest('.btn-trigger-mat-transfer-issue') : null;

                if (!btn) return;

                var $unit = unitOf($(btn).closest('.mat-issue-row'));

                if (!$unit) return;

                if (guard($unit, e)) {
                    attachCodes($(btn).closest('.form-mat-transfer-issue'), state($unit).req);
                    lastSubmitted = $unit;
                }
            }, true);

            /* ---------- Cập nhật lại khi dòng lô / bảng / dữ liệu đổi ---------- */

            // syncForm (issueLotPickerModal) phát me:synced mỗi khi đổi lô, số lượng, dựng lại dòng
            $(document).on('me:synced', '.me-issue-form', function () {
                apply($(this));
            });

            $(document).on('input change', '.input-mat-issue-amount, .select-mat-issue-unit', function () {
                apply($(this).closest('.mat-issue-row'));
            });

            // Chọn lô khác ở bảng tồn của phiếu liên phòng ban - đợi handler gốc đổi mã xong
            $(document).on('click', '.btn-select-mat-transfer-import', function () {
                setTimeout(function () { applyAll($('.modal .mat-issue-row').closest('table')); }, 0);
            });

            // DataTables sang trang khác / vẽ lại: các hàng vừa hiện ra
            $(document).on('draw.dt', function (e) {
                $(e.target).find('.me-issue-form').each(function () { apply($(this)); });
            });

            $(document).on('shown.bs.modal', '.modal', function () {
                if (this.id !== 'meScanModal' && this.id !== 'cameraScanModal') applyAll($(this));
            });

            // Sau mỗi lần cấp phát chạy ngầm: cấp thành công thì xoá các mã đã quét của đơn vị đó
            // (lần cấp tiếp phải quét lại), và màn hình gốc có thể đã mở lại nút Cấp Phát
            $(document).ajaxComplete(function (e, xhr) {
                var res = xhr && xhr.responseJSON;

                if (lastSubmitted && res && res.success) {
                    lastSubmitted.data('meScanOk', {});
                }

                lastSubmitted = null;
                setTimeout(function () { applyAll(); }, 0);
            });

            applyAll();
        });
    </script>
@endif
