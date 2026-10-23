{{--
| Tab "Hiệu chuẩn": modal QA ký xác nhận kết quả + form ẩn huỷ phiếu chờ QA.
| Route: pages.export.materialCalibration.confirm / cancel - MaterialCalibrationController.
--}}
@php $calRoute = 'pages.export.materialCalibration.'; @endphp

<div class="modal fade md-modal" id="calConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ruler-combined mr-2"></i>Xác nhận kết quả hiệu chuẩn <span class="cal-code"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($calRoute . 'confirm') }}" method="POST">
                @csrf
                <input type="hidden" name="code" value="{{ old('code') }}">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <small class="text-muted">Vật tư</small>
                            <div class="font-weight-bold cal-material"></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Phòng đề nghị</small>
                            <div class="font-weight-bold cal-department"></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Đề nghị cấp phát</small>
                            <div class="font-weight-bold"><span class="cal-request"></span> · <span class="cal-requested"></span></div>
                        </div>
                    </div>

                    {{--
                    | Số lượng + Seri No do người đề nghị nhập lúc gửi - QA không sửa được. Mỗi đơn vị một dòng,
                    | QA chọn Đạt / Không đạt (đạt một phần được). Số lượng lẻ: một dòng cho cả mã xuất nhập.
                    --}}
                    <div class="form-group">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-1" style="gap: 6px;">
                            <label class="mb-0">
                                Số lượng cần xác nhận: <b class="cal-amount text-primary"></b>
                                <span class="md-sub small font-weight-normal cal-amount-note"></span>
                            </label>
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-success cal-all" data-result="passed">
                                    <i class="fas fa-check mr-1"></i>Tất cả đạt
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger cal-all" data-result="failed">
                                    <i class="fas fa-times mr-1"></i>Tất cả không đạt
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 42vh; overflow-y: auto;">
                            <table class="table table-bordered table-sm mb-1 cal-unit-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:50px">STT</th>
                                        <th style="width:190px">Mã xuất nhập</th>
                                        <th>Seri No</th>
                                        <th class="text-center" style="width:210px">Kết quả</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="md-sub small cal-sum"></div>
                    </div>

                    <div class="form-group">
                        <label>Lý do không đạt <span class="text-danger cal-note-required" style="display:none">*</span></label>
                        <textarea name="result_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Không đạt thì ghi rõ lý do - hàng sẽ tự chuyển sang Cách ly của phòng đề nghị">{{ old('result_note') }}</textarea>
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

{{--
| NGƯỜI ĐỀ NGHỊ nhập Seri No từng đơn vị trước khi gửi QA: nút [Đề nghị HC] ở tab Chờ Cấp Phát
| (form .me-issue-form data-calibrate="1") mở modal này với đúng mã xuất nhập + số lượng đang chọn.
| Số lượng nguyên: mỗi đơn vị một Seri No bắt buộc; số lượng lẻ: một Seri No (nếu có) cho cả dòng.
--}}
<div class="modal fade md-modal" id="calRequestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ruler-combined mr-2"></i>Đề nghị hiệu chuẩn <span class="cal-req-material"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route($calRoute . 'store') }}" method="POST">
                <div class="cal-req-hidden"></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Số lượng gửi hiệu chuẩn: <b class="cal-req-amount text-primary"></b></label>
                        <div class="table-responsive" style="max-height: 46vh; overflow-y: auto;">
                            <table class="table table-bordered table-sm mb-0 cal-req-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:50px">STT</th>
                                        <th style="width:200px">Mã xuất nhập</th>
                                        <th>Seri No <span class="text-danger">*</span></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Ghi chú gửi QA</label>
                        <textarea name="request_note" rows="2" maxlength="500" class="form-control"
                            placeholder="Thông tin thêm cho QA (không bắt buộc)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane mr-1"></i> Gửi QA hiệu chuẩn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="calCancelForm" action="{{ route($calRoute . 'cancel') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="code">
    <input type="hidden" name="cancel_reason">
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $m = $('#calConfirmModal');

        function num(value) {
            return String(Math.round((parseFloat(value) || 0) * 10000) / 10000);
        }

        function esc(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        var EPS = 0.00005;

        // Tổng đạt / không đạt theo từng đơn vị; có đơn vị không đạt thì bắt buộc ghi lý do
        function syncUnits() {
            var unit = $m.data('unit') || '';
            var passed = 0;
            var failed = 0;

            $m.find('.cal-unit-row').each(function () {
                var amount = parseFloat($(this).data('amount')) || 0;
                var isFailed = $(this).find('.cal-result:checked').val() === 'failed';

                if (isFailed) failed += amount; else passed += amount;
                $(this).toggleClass('table-danger', isFailed);
            });

            $m.find('.cal-sum').html('Đạt <b class="text-success">' + num(passed) + ' ' + esc(unit) + '</b>' +
                (failed > EPS ? ' · không đạt <b class="text-danger">' + num(failed) + ' ' + esc(unit) + '</b> - chuyển sang Cách ly' : ''));
            $m.find('.cal-note-required').toggle(failed > EPS);
            $m.find('[name="result_note"]').prop('required', failed > EPS);
        }

        $m.on('change', '.cal-result', syncUnits);

        $m.on('click', '.cal-all', function () {
            $m.find('.cal-result[value="' + $(this).data('result') + '"]').prop('checked', true);
            syncUnits();
        });

        function unitRow(no, lot, item, unit) {
            var id = 'calU_' + item.id;
            var whole = !item.unit_no;

            return '<tr class="cal-unit-row" data-amount="' + item.amount + '">' +
                '<td class="text-center">' + no + '</td>' +
                '<td class="font-weight-bold">' + esc(lot.code) +
                (whole ? '<div class="md-sub small font-weight-normal">Cả ' + num(item.amount) + ' ' + esc(unit) + '</div>' : '') + '</td>' +
                '<td class="font-weight-bold" style="color: var(--primary-dark);">' + (item.serial ? esc(item.serial) : '<span class="text-muted font-weight-normal">—</span>') + '</td>' +
                '<td class="text-center">' +
                '<div class="custom-control custom-radio custom-control-inline">' +
                '<input type="radio" class="custom-control-input cal-result" id="' + id + '_p" name="units[' + item.id + ']" value="passed" checked>' +
                '<label class="custom-control-label text-success font-weight-bold" for="' + id + '_p">Đạt</label></div>' +
                '<div class="custom-control custom-radio custom-control-inline mr-0">' +
                '<input type="radio" class="custom-control-input cal-result" id="' + id + '_f" name="units[' + item.id + ']" value="failed">' +
                '<label class="custom-control-label text-danger font-weight-bold" for="' + id + '_f">Không đạt</label></div>' +
                '</td></tr>';
        }

        $(document).on('click', '.btn-cal-confirm', function () {
            var row = $(this).data('row') || {};
            var html = [];
            var no = 0;

            $m.data('unit', row.unit || '');
            $m.find('[name="code"]').val(row.code);
            $m.find('.cal-code').text(row.code || '');
            $m.find('.cal-material').text(row.material || '—');
            $m.find('.cal-department').text(row.department || '—');
            $m.find('.cal-request').text(row.request_code || '—');
            $m.find('.cal-requested').text(row.requested_text || '');
            $m.find('.cal-amount').text(row.amount_text || '');

            // Người đề nghị được gửi khác số còn phải cấp (thường là gửi dư dự phòng): ghi rõ để QA khỏi hiểu nhầm
            var diff = Math.round(((row.total || 0) - (row.left || 0)) * 10000) / 10000;
            $m.find('.cal-amount-note').text(
                diff > 0.00005 ? '(người đề nghị gửi dư ' + num(diff) + ' ' + (row.unit || '') + ' dự phòng so với số còn phải cấp ' + num(row.left) + ' ' + (row.unit || '') + ')'
                : diff < -0.00005 ? '(ít hơn số còn phải cấp ' + num(row.left) + ' ' + (row.unit || '') + ')'
                : ''
            );

            (row.lots || []).forEach(function (lot) {
                (lot.units || []).forEach(function (item) {
                    html.push(unitRow(++no, lot, item, row.unit));
                });
            });

            $m.find('.cal-unit-table tbody').html(html.join(''));
            $m.find('[name="sign_password"], [name="result_note"]').val('');
            syncUnits();
            $m.modal('show');
        });

        /* ---------- Người đề nghị nhập Seri No trước khi gửi QA ---------- */

        var $rq = $('#calRequestModal');
        var UNIT_LIMIT = {{ \App\Support\MaterialCalibration::UNIT_LIMIT }};

        // Cùng quy tắc MaterialCalibration::unitCount(): số nguyên 1..UNIT_LIMIT thì tách từng đơn vị, không thì 0
        function unitCount(amount) {
            var rounded = Math.round(amount);

            return Math.abs(amount - rounded) < 0.00005 && rounded >= 1 && rounded <= UNIT_LIMIT ? rounded : 0;
        }

        $(document).on('submit', '.me-issue-form[data-calibrate="1"]', function (e) {
            var $form = $(this);
            var categoryId = String($form.data('category-id'));
            var unit = $form.data('unit') || '';
            var lots = (window.meIssueLots || {})[categoryId] || [];
            var wanted = {};
            var order = [];

            e.preventDefault();

            $form.find('.me-issue-row').each(function () {
                var importId = String($(this).find('.me-issue-lot').val() || $(this).find('input[name$="[import_id]"]').val() || '');
                var amount = parseFloat($(this).find('.me-issue-amount').val()) || 0;

                if (!importId || amount <= 0.00005) return;
                if (!(importId in wanted)) order.push(importId);
                wanted[importId] = Math.round(((wanted[importId] || 0) + amount) * 10000) / 10000;
            });

            // Chưa chọn lô / số lượng: để handler chung (issueLotPickerModal) báo
            if (!order.length) return;

            var rows = [];
            var no = 0;
            var total = 0;

            order.forEach(function (importId) {
                var lot = lots.filter(function (l) { return String(l.id) === importId; })[0] || {};
                var amount = wanted[importId];
                var count = unitCount(amount);

                total += amount;

                for (var i = 0; i < Math.max(count, 1); i++) {
                    rows.push('<tr>' +
                        '<td class="text-center">' + (++no) + '</td>' +
                        '<td class="font-weight-bold">' + esc(lot.code || importId) +
                        (count ? '' : '<div class="md-sub small font-weight-normal">Cả ' + num(amount) + ' ' + esc(unit) + '</div>') + '</td>' +
                        '<td><input type="text" maxlength="100" class="form-control form-control-sm cal-req-serial" name="serials[' + importId + '][]"' +
                        (count ? ' required placeholder="Seri No đơn vị thứ ' + (i + 1) + '"' : ' placeholder="Seri No (nếu có)"') + '></td>' +
                        '</tr>');
                }
            });

            // Chép nguyên các trường của form cấp phát (token, item_id, lots[], bộ lọc) sang form gửi
            var hidden = $form.serializeArray().map(function (field) {
                return '<input type="hidden" name="' + esc(field.name) + '" value="' + esc(field.value) + '">';
            });

            $rq.find('.cal-req-hidden').html(hidden.join(''));
            $rq.find('.cal-req-material').text($form.data('material') || '');
            $rq.find('.cal-req-amount').text(num(total) + ' ' + unit);
            $rq.find('.cal-req-table tbody').html(rows.join(''));
            $rq.find('[name="request_note"]').val('');
            $rq.modal('show');
        });

        $rq.on('shown.bs.modal', function () {
            $rq.find('.cal-req-serial').first().trigger('focus');
        });

        // Seri No không được trùng trong phiếu (máy chủ kiểm lại)
        $rq.on('submit', 'form', function (e) {
            var seen = {};
            var dup = null;

            $rq.find('.cal-req-serial').each(function () {
                var value = $.trim($(this).val()).toLowerCase();

                if (!value) return;
                if (seen[value]) dup = $(this).val();
                seen[value] = true;
            });

            if (dup) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Seri No bị trùng', text: 'Seri No "' + dup + '" được nhập nhiều lần trong phiếu.' });
            }
        });

        $(document).on('click', '.btn-cal-cancel', function () {
            var code = $(this).data('code');

            Swal.fire({
                title: 'Huỷ đề nghị hiệu chuẩn ' + code + '?',
                text: 'Hàng đang giữ chỗ sẽ được nhả về kho.',
                input: 'text',
                inputPlaceholder: 'Lý do huỷ (không bắt buộc)',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                confirmButtonText: 'Huỷ phiếu',
                cancelButtonText: 'Đóng'
            }).then(function (result) {
                if (!result.isConfirmed) return;

                var $f = $('#calCancelForm');
                $f.find('[name="code"]').val(code);
                $f.find('[name="cancel_reason"]').val(result.value || '');
                $f.trigger('submit');
            });
        });
    });
</script>
